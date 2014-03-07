# des-sn-dbscripts

Dark Energy Survey (DES) supernova project scripts for downloading data
from the DES database.

## Installation

The scripts are stand-alone. Copy the scripts to a directory on your
`PATH`, or just specify the full path to the script when running
it. You must have the following Python libraries installed:

* Python 2.6 or 2.7
* numpy
* cx_Oracle
* desdb
* fitsio (optional)

## get-des-lightcurves

Retrieve light curves of SN candidates from database, output to various file
formats.

### Call sequence and options

```
get-des-lightcurves                      # Download ALL "good" candidates
get-des-lightcurves -n 10                # Download only 10 candidates
get-des-lightcurves 1137924              # Download candidate with SNID=1137924
get-des-lightcurves 621365 1137924 ...   # Download multiple candidates by SNID
get-des-lightcurves --infile candlist.txt  # Read SNIDs from text file
get-des-lightcurves --fake           # Get fake candidates instead of real ones
```

For full description of options, run `get-des-lightcurves --help`

### Output Formats

**ASCII (default)**

A single file per candidate. Each file has metadata lines signified by
`@` followed by a space-delimited table with a header line. Example
exerpt:

```
...
@host_photoz 0.278620004654
@host_specz NULL
@host_separation 0.518048524857
@host_photoz_err 0.0531300008297
@host_specz_catalog NULL
@host_specz_err NULL
time field band flux fluxerr zp zpsys status expnum image_id ccdnum psf_nea chip_sigsky chip_zero_point chip_zero_point_r
ms
56536.2177155 C2 g -10528.2 734.473 31.4 ab 1 229378 470978951 21 1.515 7.53202 29.996 0.083
56536.2285244 C2 r -9241.23 14385.2 31.4 ab 17 229379 470887770 21 1.674 12.2332 30.287 0.054
56536.2306145 C2 i -4916.34 2744.28 31.4 ab 17 229380 470962346 21 1.639 22.4209 30.856 0.092
56536.2332765 C2 z 588.089 715.499 31.4 ab 1 229381 470944977 21 1.4915 46.8239 31.2981 0.081
...
```

**JSON**

A single file per candidate in standard JSON format. The top-level
structure is a two item dictionary with `meta` and `data` keys.

**FITS**

This format option compiles all candidate light curves into a single FITS
file, rather than creating a separate file for each candidate.

```
get-des-lightcurves --format fits -o candidates.fits
```

The `-o` must be specified and must end in `.fits`. The file cannot already
exist.

The format is a two-extension FITS file where both extensions are
binary tables.  The first extension contains all candidates' metadata
while the second extension contains all candidates' photometry. In the
first extension, there is one row per candidate, and in the second
extension there are multiple rows per candidate. The first extension
contains two extra columns `datastart` and `dataend` giving the
location of the candidate's photometry in the second extension. Here
is an example of how to loop over the first 10 candidates in the file using the `fitsio` python
package:

```python
f = fitsio.FITS("candidates.fits")  # open file for reading
for meta in f[1][0:10]:

    print 'SNID:', meta['snid']
    
    start = meta['datastart']
    end = meta['dataend']

    data = f[2][start:end]
    # ... do stuff with data, which is a numpy structured array
```


## get-des-obsinfo

Retrieve observing conditions (image metadata) for a random set of
positions from the database, write to an SNANA "SIMLIB" format file.

```
get-des-obsinfo -o output.txt          # Generate 100 random positions (default)
get-des-obsinfo -n 1000 -o output.txt  # Generate 1000 random positions
get-des-obsinfo --infile positions.txt -o output.txt  # Read positions from file
get-des-obsinfo -c -o output.txt       # Cache DB query results in local file
```
