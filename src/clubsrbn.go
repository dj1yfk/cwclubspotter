//
// RBN Server implemented in Go, written by Fabian Kurz, DJ5CW (ex DJ1YFK) <fabian@fkurz.net>
//
// This Go program provides the telnet server to which the users
// connect, and a minimalistic user interface.
//
// A Redis database (pub/sub) provides the spots. The filters
// are set by the web interface (https://rbn.telegraphy.de/)
//
// Repository: https://git.fkurz.net/dj1yfk/cwclubspotter/

package main

import (
	"bufio"
	"bytes"
	"encoding/binary"
	"fmt"
	"github.com/garyburd/redigo/redis"
	"github.com/op/go-logging"
	"io/ioutil"
	"net"
	"os"
	"os/signal"
	"strconv"
	"strings"
	"sync"
	"syscall"
	"time"
)

// Globals
var helptext = "\r\n- set/clubs       Show filtered spots\r\n- set/raw         Show all unfiltered RBN spots\r\n\r\nNo spots? Go to https://rbn.telegraphy.de/, enter your callsign\r\nand edit your filters!\r\n"
var filter_names = map[string]string{"clubs": "Clubs (filtered)", "raw": "Raw unfiltered spots"}

// build info should be set by Makefile or build process
var build = "<unknown>"

// map from connection (IP:Port) -> callsign
var users map[net.Addr]string

// user prefs (set/raw or set/clubs) per user
var prefs map[string]string

// output format (normal/ve7cc) per user (volatile)
var format map[string]string

// dedupe the output (only one spot per call and freq every 5 min)
var dedupe map[string]bool

// filters per user, as set by web interface
type UFilter struct {
	club   uint64
	cont   byte
	speed  byte
	band   uint16
	block  map[string]bool
	reload time.Time
}

// if set to true, the TCP listeners will stop
// so we can deploy a new version without killing
// existing connections.
var stop_listeners bool

var log = logging.MustGetLogger("clubsrbn.go")

var prod bool

var mutex = &sync.Mutex{}

func main() {
	go handleSignals()

	setupLogging()
	users = make(map[net.Addr]string)
	prefs = make(map[string]string)
	format = make(map[string]string)
	dedupe = make(map[string]bool)

	// Launch listeners
	if len(os.Args) == 2 && os.Args[1] == "prod" {
		prod = true
		readPrefs() // read prefs from file
		log.Infof("Clubs RBN Server Production Mode, build: %s\n", build)
		go listenerStart(":7000")
	} else {
		readPrefs() // read prefs from file
		log.Infof("RBN Server Debug Mode, build: %s\n", build)
		go listenerStart(":8000")
		prod = false
	}

	reader := bufio.NewReader(os.Stdin)
	for {
		text, _ := reader.ReadString('\n')
		if strings.Contains(text, "stoplisten") {
			log.Debug("Stopping listeners after next connection.")
			stop_listeners = true
		}

		log.Debug(printAllUsers(true))
	}

} // main

func listenerStart(service string) {
	listener, err := net.Listen("tcp", service)
	checkError(err)

	for {
		if stop_listeners == true {
			log.Debugf("Listener %s stopping\n", service)
			listener.Close()
			return
		}
		conn, err := listener.Accept()
		if err != nil {
			continue
		}
		go handleClient(conn)
	}
}

func handleClientClose(conn net.Conn, ch chan string) {
	log.Debugf("defer handleClientClose from %s\n", conn.RemoteAddr())
	ra := conn.RemoteAddr()
	mutex.Lock()
	delete(users, ra)
	mutex.Unlock()
	conn.Close()
	ch <- "end"
}

func handleClient(conn net.Conn) {

	var login string

	// control channel from client input handling to outputClient
	control := make(chan string)

	login = promptLogin(conn)

	if login == "" {
		handleClientClose(conn, control)
	}

	conn.Write([]byte(fmt.Sprintf("User: %s, Current filter: %s\r\n%s\r\n",
		login, filter_names[prefs[login]], helptext)))
	conn.Write([]byte(prompt(login)))

	defer handleClientClose(conn, control)

	// This goroutine will take care of spot output
	go outputClient(conn, control, login)

	// Main user input processing loop
	for {
		cmd, err := readFullLine(conn, true, true)

		if err != nil {
			return
		}

		cmd = strings.ToLower(cmd)

		switch {
		case (cmd == "exit") || (cmd == "quit") || (cmd == "bye"):
			control <- "end"
			return
		case strings.Contains(cmd, "sh/u"):
			log.Debugf("%s: sh/u\n", login)
			conn.Write([]byte(printAllUsers(false)))
		case strings.Contains(cmd, "set/clubs"):
			log.Debugf("%s: clubs: %s\n", login, cmd)
			prefs[login] = "clubs"
			control <- "end"
			go outputClient(conn, control, login)
			conn.Write([]byte("New filter: Club members (as set in web interface)\r\n"))
		case strings.Contains(cmd, "set/raw"):
			log.Debugf("%s: switch to raw: %s\n", login, cmd)
			prefs[login] = "raw"
			control <- "end"
			go outputClient(conn, control, login)
			conn.Write([]byte("New filter: Raw RBN spots\r\n"))
		case strings.Contains(cmd, "help"):
			log.Debugf("%s: help\n", login)
			conn.Write([]byte(helptext))
		case strings.Contains(cmd, "set/ve7cc"):
			log.Debugf("%s: switch to VE7CC format\n", login)
			format[login] = "ve7cc"
			control <- "end"
			go outputClient(conn, control, login)
			conn.Write([]byte("New format: VE7CC\r\n"))
		case strings.Contains(cmd, "set/normal"):
			log.Debugf("%s: switch to normal DX cluster format\n", login)
			format[login] = "normal"
			control <- "end"
			go outputClient(conn, control, login)
			conn.Write([]byte("New format: Normal\r\n"))
		case strings.Contains(cmd, "set/nodupes"):
			log.Debugf("%s: Dedupe on\n", login)
			dedupe[login] = true
			control <- "end"
			go outputClient(conn, control, login)
			conn.Write([]byte("Suppressing duplicate spots (one spot per call and band every 5 minutes)\r\n"))
		case strings.Contains(cmd, "set/dupes"):
			log.Debugf("%s: Dedupe off\n", login)
			dedupe[login] = false
			control <- "end"
			go outputClient(conn, control, login)
			conn.Write([]byte("Enabling duplicate spots.\r\n"))
		default:
			log.Debugf("%s: Unknown command >%s< (ignored)\n", login, cmd)
		}

		conn.Write([]byte(prompt(login)))
	}

}

func prompt(user string) string {
	t := time.Now().UTC()
	return fmt.Sprintf("%s de DJ5CW-2 %sZ dxspider >\r\n", user, t.Format("_2-Jan-2006 1504"))
}

func promptLogin(conn net.Conn) (login string) {

	login_before_prompt := true
	// Before we send anything, check for possible HTTP request...
	conn.SetReadDeadline(time.Now().Add(100 * time.Millisecond))

	login, err := readFullLine(conn, false, false)
	if err != nil {
		log.Debugf("Nothing received within 100ms, probably a real client.\n")
		login_before_prompt = false
	}

	if strings.Contains(login, "GET/HTTP") {
		log.Warningf("HTTP request... send redirect\n")
		conn.Write([]byte("HTTP/1.1 302 Found\r\nLocation: https://rbn.telegraphy.de/\r\n\r\nhttps://rbn.telegraphy.de/"))
		return ""
	}

	conn.Write([]byte("Welcome to the CW Clubs RBN (Ver. " + build + ")\r\nPlease enter your callsign: "))

	// early login already sent?
	if login_before_prompt == false {
		// Timeout for the login: 60s
		conn.SetReadDeadline(time.Now().Add(60 * time.Second))
		login, err = readFullLine(conn, false, true)

		if err != nil {
			return ""
		}
	}

	// "Valid" call?
	if len(login) < 3 || len(login) > 10 {
		log.Warningf("Invalid login: %s\n", login)
		conn.Write([]byte("Invalid call. Bye.\r\n"))
		return ""
	}

	var zero time.Time
	conn.SetReadDeadline(zero)
	login = strings.ToUpper(login)

	if prefs[login] == "" {
		prefs[login] = "clubs"
	}

	ra := conn.RemoteAddr()
	mutex.Lock()
	users[ra] = login // save in map
	mutex.Unlock()

	log.Debug(printAllUsers(true))
	log.Infof("User logged in: %s (%s)\n", login, ra)

	return login
}

func outputClient(conn net.Conn, control <-chan string, login string) {
	defer log.Debug("outputClient => close\n")

	spots := make(chan string)
	rediscontrol := make(chan string)
	filter := prefs[login]
	dedupehash := make(map[string]time.Time) // key = DJ5CW + MHz + 100 kHz (e.g. DJ5CW on 5354.0 kHz: DJ5CW53 -> time of last spot)

	var ufilter UFilter
	ufilter.reload = time.Now().Add(time.Duration(-5) * time.Second)
	ufilter.block = make(map[string]bool)

	go subscribeSpots(filter, spots, rediscontrol)

	for {
		select {
		case cs := <-control:
			log.Debugf("Control signal: >%s<\n", cs)
			if cs == "end" {
				rediscontrol <- "die"
				return
			}
		case spot := <-spots:
			// reload this user's filters from web interface every 5 seconds
			reloadUserFilter(login, &ufilter)

			if filter == "clubs" {
				// spot contains:
				// 1 byte  continent
				// 8 bytes clubs
				// 1 byte  speed
				// 2 bytes band
				// n bytes spot (plain ascii), no newline

				b := []byte(spot)

				cont := b[0]
				clubs := binary.LittleEndian.Uint64(b[1:9])
				speed := b[9]
				band := binary.LittleEndian.Uint16(b[10:13])
				spot = string(b[12:]) // spot w/o the bit fields

				// no club prefs stored for this call => pass all QSOs, regardless
				// of club membership
				all_calls := ufilter.club == 0

				if !(cont&ufilter.cont != 0 && (clubs&ufilter.club != 0 || all_calls) && speed&ufilter.speed != 0 && band&ufilter.band != 0) {
					continue // spot does not match the filter
				}
			}

			if len(ufilter.block) > 0 && callOnBlockList(spot, ufilter.block) {
				continue
			}

			if dedupe[login] == true {
				if isDupe(spot, dedupehash) {
					continue
				}
			}

			if format[login] == "ve7cc" {
				spot = formatVe7cc(spot)
			}

			conn.Write([]byte(spot))

		}
	}
}

func callOnBlockList(spot string, list map[string]bool) bool {
	spot = strings.Replace(spot, "-#:", " ", -1)
	spot = strings.Replace(spot, "DX de ", "", -1)
	s := strings.Fields(spot)
	call := s[2]

	if list[call] {
		return true
	}
	return false

}

func reloadUserFilter(login string, ufilter *UFilter) {

	if time.Now().Sub(ufilter.reload) < 5*time.Second {
		return
	}

	ufilter.reload = time.Now()

	c, _ := redis.Dial("tcp", "localhost:6379")
	defer c.Close()
	ret, _ := c.Do("HGET", "rbnprefs", login)
	block, _ := c.Do("HGET", "rbnblock", login)

	if ret == nil {
		// no preferences found. if it is a callsign with a SSID, try without
		l := strings.LastIndex(login, "-")
		if l == -1 {
			ufilter.cont = 0xff
			ufilter.club = 0
			ufilter.speed = 0xff
			ufilter.band = 0xffff
			return
		}

		retstrip, _ := c.Do("HGET", "rbnprefs", login[0:l])
		blockstrip, _ := c.Do("HGET", "rbnblock", login[0:l])

		// no success either
		if retstrip == nil {
			ufilter.cont = 0xff
			ufilter.club = 0
			ufilter.speed = 0xff
			ufilter.band = 0xffff
			return
		} else {
			ret = retstrip
			block = blockstrip
		}
	}

	ret2 := []byte(ret.([]uint8))

	ufilter.cont = ret2[0]
	ufilter.club = binary.LittleEndian.Uint64(ret2[1:9])

	if len(ret2) > 10 {
		ufilter.speed = ret2[9]
		ufilter.band = binary.LittleEndian.Uint16(ret2[10:])
	} else {
		ufilter.speed = 0xff
		ufilter.band = 0xffff
	}

	if block != nil {
		bs := string(block.([]uint8))
		// log.Debugf("block list of %s = %s %T\n", login, block, bs)
		var bl = strings.Split(bs, " ")
		for k := range ufilter.block {
			delete(ufilter.block, k)
		}
		for i := 0; i < len(bl); i++ {
			ufilter.block[bl[i]] = true
		}
	}
}

func isDupe(spot string, dedupehash map[string]time.Time) bool {
	spot = strings.Replace(spot, "-#:", " ", -1)
	spot = strings.Replace(spot, "DX de ", "", -1)
	s := strings.Fields(spot)
	call := s[2]
	freq, err := strconv.ParseFloat(s[1], 32)
	if err != nil {
		return false
	}
	freq_short := int(freq / 100)
	hashkey := call + string(freq_short)

	dupetime, exists := dedupehash[hashkey]

	if exists {
		if time.Now().Sub(dupetime) > 5*time.Minute {
			// log.Debugf("DUPE expired: %s %s\n", spot, dupetime)
			delete(dedupehash, hashkey)
			dedupehash[hashkey] = time.Now()
			return false
		} else {
			// log.Debugf("DUPE: %s %s\n", spot, dupetime)
			return true
		}
	} else {
		dedupehash[hashkey] = time.Now()
		return false
	}

	return false
}

// generate CC11 format strings:
// in: DX de GM6DX-#:    7021.4  DL2IAD         CW    12 dB  23 WPM  CQ      1352Z
// out:
// CC11^7021.4^DL2IAD^ 4-May-2020^1352Z^CW    12 dB  23 WPM  CQ^GM6DX^^^DJ5CW^^^^^^^^^^
//       freq    dxc     date       utc   rem    spotter   dxcc etc. optional
func formatVe7cc(spot string) string {
	//    DX de GM6DX-#:    7021.4  DL2IAD         CW    12 dB  23 WPM  CQ      1352Z
	spot = strings.Replace(spot, "-#:", " ", -1)
	spot = strings.Replace(spot, "DX de ", "", -1)
	//    GM6DX    7021.4  DL2IAD         CW    12 dB  23 WPM  CQ      1352Z
	s := strings.Fields(spot)
	out := ""
	if len(s) >= 10 && len(spot) > 33+28 {
		t := time.Now().UTC()
		out = "CC11^" + s[1] + "^" + s[2] + "^" + t.Format("_2-Jan-2006") + "^" + s[9] + "^" + spot[33:33+28] + "^" + s[0] + "^^^DJ5CW^^^^^^^^^^\r\n"
	}
	return out
}

// Retrieves spots from Redis and returns them into spots chan
func subscribeSpots(filter string, spots chan string, control chan string) {
	defer log.Debug("subscribeSpots => close\n")

	pattern := "rbn"

	if filter == "raw" {
		pattern = "raw"
	}

	c, _ := redis.Dial("tcp", "localhost:6379")
	defer c.Close()
	psc := redis.PubSubConn{c}
	psc.Subscribe(pattern)

	for {
		// select with only one case + default: non-blocking
		select {
		case msg := <-control:
			log.Debugf("Control message received: %s\n", msg)
			return
		default:
		}
		switch v := psc.Receive().(type) {
		case redis.Message:
			// select with only one case + default: non-blocking
			select {
			case spots <- string(v.Data):
			default:
			}

		}
	}

}

func checkError(err error) {
	if err != nil {
		log.Errorf("Fatal error: %s\n", err.Error())
		os.Exit(1)
	}
}

func printAllUsers(ip bool) string {

	var buf bytes.Buffer

	buf.WriteString(fmt.Sprintf("All connected users (%d)\r\n=========================\r\n", len(users)))

	mutex.Lock()
	for k, v := range users {
		if ip {
			buf.WriteString(fmt.Sprintf("%-20s %-25s %s\r\n", v, k, filter_names[prefs[v]]))
		} else {
			buf.WriteString(fmt.Sprintf("%-20s %s\r\n", v, filter_names[prefs[v]]))
		}
	}
	mutex.Unlock()

	return buf.String()
}

// Reads a fill line (ending with (\r)\n) from conn
// (blocking) and returns it as a string without
// any telnet control characters (sequences starting
// with 0xff + 2 chars) and other unprintable chars

func readFullLine(conn net.Conn, allowspace bool, reporterror bool) (string, error) {
	var buf [512]byte

	defer func() {
		if r := recover(); r != nil {
			log.Warningf("readFullLine: PANIC! ", r)
		}
	}()

	rxlen := 0

	only_printable := func(r rune) rune {
		switch {
		case (r >= 'A' && r <= 'Z') || (r >= 'a' && r <= 'z'):
			return r
		case (r >= '0' && r <= '9') || (r == '/') || (r == '-') || (allowspace && r == ' '):
			return r
		}
		return -1
	}

	for {
		n, err := conn.Read(buf[rxlen:])

		if err != nil {
			if reporterror {
				log.Debugf("Read from client: %s\n", err.Error())
			}
			return "", err
		}

		rxlen += n

		if rxlen >= 256 {
			log.Warningf("readFullLine: Overflow. Flushing input.")
			return "", nil
		}

		var cleanbuf []byte

		// do we have \r\n (cut 2 bytes) or only \n (cut 1 byte)?
		var cut int

		// check if the line is complete?
		if rxlen > 0 && buf[rxlen-1] == '\n' {

			if rxlen > 1 && buf[rxlen-2] == '\r' { // \r\n
				cut = 2
			} else { // only \n
				cut = 1
			}

			// Find last occurence of 0xff (control char)
			// and then remove this plus the next two chars
			// and everything before it.
			for i := rxlen; i > 0; i-- {
				if buf[i] == 0xff {
					cleanbuf = buf[i+2 : rxlen-cut] // cut \r\n
					break
				}
			}

			var out string
			if cleanbuf == nil {
				out = string(buf[0 : rxlen-cut])
			} else {
				out = string(cleanbuf)
			}

			// there may still be a (\r)\n somewhere in the string.
			// cut everything after it
			i := strings.IndexByte(out, '\r')
			if i >= 0 {
				out = out[:i]
			}

			i = strings.IndexByte(out, '\n')
			if i >= 0 {
				out = out[:i]
			}

			// Remove everything else that is not part of a call or command
			out = strings.Map(only_printable, out)

			return out, nil
		}
	}
}

func handleSignals() {
	sigchan := make(chan os.Signal)
	signal.Notify(sigchan, syscall.SIGINT, syscall.SIGTERM)

	select {
	case s := <-sigchan:
		log.Warningf("Caught signal: %s\n", s)
		savePrefs()
		os.Exit(0)
	}
}

func readPrefs() {

	var prefs_file = "userprefs_rbngo"
	if prod == false {
		prefs_file = "userprefs_rbngo_dev"
	}

	log.Infof("Loading user prefs (%s)...\n", prefs_file)

	f, _ := os.Open(prefs_file)
	defer f.Close()

	scanner := bufio.NewScanner(f)

	for scanner.Scan() {
		line := scanner.Text()
		sp := strings.Split(line, ";")
		prefs[sp[0]] = sp[1]
	}

}

func savePrefs() {
	log.Info("Saving user prefs...\n")

	var buf bytes.Buffer

	for k, v := range prefs {
		buf.WriteString(fmt.Sprintf("%s;%s\n", k, v))
	}

	if prod {
		ioutil.WriteFile("userprefs_rbngo", buf.Bytes(), 0666)
	} else {
		ioutil.WriteFile("userprefs_rbngo_dev", buf.Bytes(), 0666)
	}
}

func setupLogging() {
	var format = logging.MustStringFormatter(
		`%{color}%{time:15:04:05.000} %{shortfunc} ▶ %{level:.4s} %{id:03x}%{color:reset} %{message}`,
	)

	log_stderr := logging.NewLogBackend(os.Stderr, "", 0)
	log_syslog, _ := logging.NewSyslogBackend("")

	log_stderr_fmt := logging.NewBackendFormatter(log_stderr, format)
	log_syslog_lvl := logging.AddModuleLevel(log_syslog)
	log_syslog_lvl.SetLevel(logging.INFO, "")

	logging.SetBackend(log_stderr_fmt, log_syslog_lvl)
}
