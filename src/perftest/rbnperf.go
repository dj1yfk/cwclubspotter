// RBN Server Performance test
//
// Repository: https://git.fkurz.net/dj1yfk/cwclubspotter/

package main

import (
	"fmt"
	"net"
    "time"
)

func main() {
    for i := 0; i < 500; i++ {
        go client(i);
    }

    for {
        time.Sleep(2*time.Second)
    }

}

func client (i int){
	var buf [1024]byte
    lines := 0

    conn, err := net.Dial("tcp", "localhost:8000")
    if err != nil {
        fmt.Printf("Connection %d refused!", i);
        return
    }
    defer conn.Close()
    conn.Write([]byte("MY1CALL\n"))
    conn.Write([]byte("set/clubs\n"))


	for {
		_, err := conn.Read(buf[0:])

		if err != nil {
			fmt.Print("Telnet connection died.\n")
			return
		} else {
            lines = lines + 1
            if lines % 100 == 0 {
                fmt.Printf("Client %d => %d lines received...\n", i, lines)
            }
			// fmt.Printf("RX: %s\n", string(buf[0:l]))
		}
	}

}
