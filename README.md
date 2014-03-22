# des-sn-dbscripts

Dark Energy Survey (DES) supernova project scripts for downloading data
from the DES database.

## Installation

The scripts are stand-alone. Copy the scripts to a directory on your
path, or just specify the full path to the script when running
it. You must have the following Python version and libraries installed:

* Python 2.6 or 2.7
* cx_Oracle
* [desdb](https://github.com/esheldon/desdb)
* numpy
* [fitsio](https://github.com/esheldon/fitsio) (optional for FITS output in get-des-lightcurves)

The desdb package is used for credential management so that database
credentials can be kept in an external `.netrc` file. See the
desdb package for details. It is a pure Python package and therefore
should be easy to install from source.

NumPy is used to more easily manipulate tables of downloaded data.

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
... [clipped] ...
@host_photoz 0.278620004654
@host_specz NULL
@host_separation 0.518048524857
@host_photoz_err 0.0531300008297
@host_specz_catalog NULL
@host_specz_err NULL
time field band flux fluxerr zp zpsys status expnum image_id ccdnum psf_nea chip_sigsky chip_zero_point chip_zero_point_rms
56536.2177155 C2 g -10528.2 734.473 31.4 ab 1 229378 470978951 21 1.515 7.53202 29.996 0.083
56536.2285244 C2 r -9241.23 14385.2 31.4 ab 17 229379 470887770 21 1.674 12.2332 30.287 0.054
56536.2306145 C2 i -4916.34 2744.28 31.4 ab 17 229380 470962346 21 1.639 22.4209 30.856 0.092
56536.2332765 C2 z 588.089 715.499 31.4 ab 1 229381 470944977 21 1.4915 46.8239 31.2981 0.081
... [clipped] ...
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
is an example of how to loop over the first 10 candidates in the file
using the `fitsio` python package:

```python
import fitsio

f = fitsio.FITS("candidates.fits")  # open file for reading
for meta in f[1][0:10]:

    print 'SNID:', meta['snid']
    
    start = meta['datastart']
    end = meta['dataend']

    data = f[2][start:end]
    # ... do stuff with data, which is a numpy structured array
```

with the `astropy.io.fits` package, this would instead be:

```python
from astropy.io import fits

f = fits.open("candidates.fits")
for meta in f[1].data[0:10]:
    start = meta['datastart']
    end = meta['dataend']
    data = f[2].data[start:end]
    # ... do stuff with data array
```

in this case, `data` is a `FITS_rec` object rather than a
`numpy.ndarray` object. It should act mostly like a structured array,
but if you have problems, you can do `data = data.view(np.ndarray)` to
explicitly get a view of the object as a structured array.




## get-des-obsinfo

Retrieve observing conditions (image metadata) for a random set of
positions from the database, write to an SNANA "SIMLIB" format file.

```
get-des-obsinfo -o output.txt          # Generate 100 random positions (default)
get-des-obsinfo -n 1000 -o output.txt  # Generate 1000 random positions
get-des-obsinfo --infile positions.txt -o output.txt  # Read positions from file
get-des-obsinfo -c -o output.txt       # Cache DB query results in local file
```

(Run `get-des-obsinfo --help` for full option list and descriptions.)

If the cache option `-c` is specified, a file named
`des-obsinfo-cache.npy` is created in the current directory. This
contains the results of the database query (metadata about images in
SN fields). The database query takes the vast majority of the runtime,
so use this option when running mulitple times or when testing. When
starting, `get-des-obsinfo` first checks if such a file exists; if so,
the file is read and no database query is performed.

## get-cand-imageinfo

Given a candidate SNID or position, retrieve information about images
that contain it.  The information retrieved is sufficient to determine
the path of the images so that they can be downloaded from NCSA.

In order to tell if a given position is contained in a given image,
the full WCS solution of the image is required, including `PV?_??`
keywords. This script downloads the WCS values for every image in the
SN fields from the database, and uses the WCS solution to determine
which images cover the given position(s). Here is an example:

```bash
$ get-cand-imageinfo 640759
Querying database (this may take a few minutes)...
Query took 249.1 seconds, 266.95 MB array.
Saving to des-imageinfo-cache.npy...
575319 total images from SN fields.
221695 images after removing re-runs.
SNID   640759: ra=53.285743 dec=-27.902316
               928 images / 928 unique exposures / 5 unique ccds
               Wrote image info to 640759.csv
```

The first time the script is run, it has to download metadata for all
images in the SN fields, so it takes a long time. The script caches
the results of the long-running query in a file named
`des-iamgeinfo-cache.npy` and uses the cache file (if available) on
subsequent runs, which are then much faster.

In the above example, the file `test.csv` will contain:

```
field,band,expnum,ccd,latestrun,imagetype,imagename
C3,g,149739,23,20130720091122_20121110,red,DECam_149739_23.fits.fz
C3,g,149740,23,20130720091122_20121110,red,DECam_149740_23.fits.fz
C3,g,149741,23,20130720091122_20121110,red,DECam_149741_23.fits.fz
[... etc ...]
```

From this information, images can be fetched from NCSA directories using the
pattern:

```
https://${HOST}/DESFiles/desardata/OPS/red/${LATESTRUN}/red/DECam_${08d:EXPNUM}/${IMAGENAME}
```
where `$HOST` is the NCSA file server.

### Difference image mode

By default, metadata for reduced ("red") images is retrieved. To instead
get information for difference images, run with the `--diff` flag. 

If run in `--diff` mode, the path to the data will be

```
https://${HOST}/DESFiles/desardata/OPS/diff/${LATESTRUN}/${IMAGETYPE}/DECam_${08d:EXPNUM}/${IMAGENAME}
```

for example,

```
https://${HOST}/DESFiles/desardata/OPS/diff/20140211085833_20140209/diff_single_diff/DECam_00283732/SN_283732_SN-C2_tile20_g_01_fakeSN_diff_mh.fits
```

### Arbitrary positions

Rather than supplying an SNID, one can supply an RA, Dec. pair or multiple
pairs:

```
get-cand-imageinfo 37.3066,-4.33395
get-cand-imageinfo 37.3066,-4.33395 34.05,-3.80
```

This also works with the `--diff` option. Though arbitrary coordinates
are accepted, only images in the SN fields are checked for overlap.