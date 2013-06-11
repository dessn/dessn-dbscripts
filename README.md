des-sn-tools
============

DES Supernova-related scripts and utilities. This repository is intended for
stand-alone scripts and modular functionality (such as python modules). When
adding a script to the `scripts` directory, describe its functionality and
requirements here.

Scripts
-------

These scripts are run from the command line. The 

* `get-des-lightcurves`: Retrieves light curves of SN candidates from database,
  writes each to a file in chosen directory. Currently only writes in SNANA
  format. [Requires the `desdb` and the `dessn` python modules. See below.]

* GPS_cronjob_forSuominet.pl :  cronjob to prepare GPS files for Suominet
   to measure precipitable water vapor (PWV). See top of script for
   usage instructions.


`dessn` Python module
---------------------

The python module `dessn` provides stand-alone functions, briefly
described below. See the function docstrings in the code itself for
more detailed descriptions and examples.

### Reading and writing

* `dessn.read_rk()`: Read text files like those produced by fakeMatch and
  filterObj into dictionaries or numpy arrays.
  _("rk" is for Rick Kessler, who I think invented this format.
  Is there a better name for this?)_
* `dessn.read_rk_multi()`: Like above, but read from multiple similar files
  and glue results together into big tables.
* `dessn.writelc()`: Write light curve data to a file of the given format.
  Currently only SNANA supported.

### Statistics

* `dessn.binned_binomial_proportion()`: Useful for making plots of efficiency
  versus magnitude with "correct" errorbars. Same as the function of the
  same name in astropy.stats. See
  [documentation in astropy.stats](http://astropy.readthedocs.org/en/latest/_generated/astropy.stats.funcs.binned_binom_proportion.html)
  for nicely HTML-formatted description.
* `dessn.binom_conf_interval()`: Binomial confidence interval on underlying
  probability, given n trials and k successes. Used in above function.

### General utilities

* `dessn.dict_to_array()`: Convert dictionary of lists to a numpy array


Installation: Python module and python script(s):
-------------------------------------------------

### Install instructions

Do

    python setup.py install

or

    python setup.py install --user

This should make the python module available and install any python scripts
in your path. For other scripts, you may have to copy them to some directory
in your path by hand.

Alternatively, you can add the `scripts` directory to your $PATH, and
add the base repository directory to your $PYTHONPATH.
 
### Dependencies

* Python 2.7
* NumPy
* SciPy (but only needed for stats functions)
* cx_Oracle (only needed for db interactions):
  http://cx-oracle.sourceforge.net
  https://pypi.python.org/pypi/cx_Oracle
* [desdb](https://github.com/esheldon/desdb)
  (only needed for db interactions)

Note: The `desdb` package is used for its handling of database usernames
and passwords so that this information can be external to the source code.

### Note

If anyone is constrained to Python 2.6 or before, we can arrange support for it.
