package main

import (
	"log"

	"github.com/jung-kurt/gofpdf"
)

func main() {
	pdf := gofpdf.New("P", "mm", "A4", "")
	pdf.AddPage()
	pdf.SetFont("Arial", "B", 16)
	pdf.Cell(40, 10, "Hello, world")
	pdf.Cell(40, 10, "Hellsdfsadfo, world")
	if err := pdf.OutputFileAndClose("hello.pdf"); err != nil {
		log.Printf("failed to output: %v", err)
	}
}
