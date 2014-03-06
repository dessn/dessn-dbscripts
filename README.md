# des-sn-dbscripts

Dark Energy Survey (DES) supernova project scripts for downloading data
from the DES database.

### Installation

The scripts are stand-alone. Copy the scripts to a directory on your
`PATH`, or just specify the full path to the script when running
it. You must have the following Python libraries installed:

* Python 2.6+
* numpy
* cx_Oracle
* desdb

### get-des-lightcurves

Retrieves light curves of SN candidates from database, writes each to
a file in chosen directory.

### get-des-obsinfo

Retrieve observing conditions (image metadata) for a random set of
positions from the database, write to an SNANA "SIMLIB" format file.
