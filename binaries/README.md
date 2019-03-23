# Installation guide for Checkmol / Matchmol libraries

The Checkmol/Matchmol libraries are developed by Norbert Haider, see the [homepage](http://merian.pch.univie.ac.at/~nhaider/cheminf/cmmm.html)

## Using the pre-compiled files 
The checkmol/matchmol libraries are precompiled for MacOS and Linux (tested on Debian and Ubuntu). 
To use these libraries, copy the compiled checkmol and matchmol files to the /usr/local/bin path on the server.

## How to compile the checkmol/matchmol libraries from pascal 
However the libraries have been tested on Ubuntu 16.04 and Raspbian (Debian), it may not work on all Linux distributions. Therefore, you might choose to compile from source. 

### Prerequisites
- Free Pascal <br />
> Required for the compilation of checkmol/matchmol <br />
> https://www.freepascal.org/

### Setup
1. Install Free Pascal
2. Compile checkmol/matchmol:
`fpc checkmol.pas -S2 -O3 -Op3`

3. Copy the compiled checkmol to /usr/local/bin and make a link to matchmol:
`cp checkmol /usr/local/bin`
`cd /usr/local/bin`
`ln checkmol matchmol`

4. Copy mol2svg to /usr/local/bin and give executable rights
`cp mol2svg /usr/local/bin`
`cd /usr/local/bin`
`sudo chmod 755 mol2svg` or `sudo chmod -x mol2svg`
