dessn
=====

DES Supernova-related scripts and utilities

Scripts
-------
* `get-des-lightcurves`: Retrieves light curves of SN candidates from database,
  writes each to a file in chosen directory. Currently only writes in SNANA
  format.


Python module
-------------

The python module `dessn` provides stand-alone functions, briefly
described below. See the function docstrings in the code itself for
more detailed descriptions and examples.

### Reading and writing

* `dessn.read_rk()`: Read text files like those produced by fakeMatch and
  filterObj into dictionaries or numpy arrays.
  _("rk" is for Rick Kessler, who I think originated this format.
  Let me know if there is a better name for it.)_
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
* `dessn.binom_conf_interval()`: Binomial confidence interval on underlying probability, given n trials and k successes. Used in above function.

### General utilities

* `dessn.dict_to_array()`: Convert dictionary of lists to a numpy array


Installation
------------

### Code install

Get the source, cd into the directory containing this README, then do

    python setup.py install --user

This should make the python module available and install the scripts
in your path.

Alternatively, you can add the `scripts` directory to your $PATH, and
add the base repository directory to your $PYTHONPATH.
 
### Dependencies

* Python 2.7
* NumPy
* SciPy (but only needed for stats functions)
* cx_Oracle (only needed for db interactions)
* [desdb](https://github.com/esheldon/desdb)
  (only needed for db interactions)

desdb is used for its handling of database usernames and passwords so that
this information can be external to the source code.

### Note

If anyone is constrained to Python 2.6 or before, we can arrange support for it.
