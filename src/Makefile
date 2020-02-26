run:
	go fmt clubsrbn.go
	go run clubsrbn.go

build:
	go fmt clubsrbn.go
	go build -o clubsrbn -ldflags '-X main.build='$(shell git log -1 --date=short --abbrev-commit --pretty=format:"%aI %h" | tr -d '\n' | sed 's/ /_/g' )
