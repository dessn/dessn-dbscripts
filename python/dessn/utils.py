try:
    from collections import OrderedDict as odict
except ImportError:
    odict = dict
import numpy as np

__all__ = ['dict_to_array', 'read_rk', 'read_rk_multi']

def dict_to_array(d):
    """Convert a dictionary of lists (of equal length) to a numpy array."""

    # first convert all lists to 1-d arrays, in order to let numpy
    # figure out the necessary size of the string arrays.
    dtype = []
    for key in d: 
        d[key] = np.array(d[key])
        dtype.append((key, d[key].dtype))
    
    # Initialize ndarray and then fill it.
    firstkey = d.keys()[0]
    result = np.empty(len(d[firstkey]), dtype=dtype)
    for key, col in d.iteritems():
        result[key] = col

    return result

def read_rk(fname, default_tablename=None, array=True):
    """Read a text file with a format typically used in SNANA and DES
    pipeline components.

    Such files may contain metadata lines and one or more tables. See Notes
    for a summary of the format.

    Parameters
    ----------
    fname : str
        Filename of object to read.
    array : bool, optional
        If True, each table is converted to a numpy array. If False, each
        table is a dictionary of lists (each list is a column). Default is
        True.

    Returns
    -------
    meta : dict
        Metadata.
    tables : dictionary of multiple `numpy.ndarray`s or dictionaries.
        The data.

    Notes
    -----
    The expected format is roughly as follows:

    * Newlines are equivalent to spaces.
    * Header with 'KEYWORD: value' pairs (optional) 
    * One or more tables. The start of a new table is indicated by a
      keyword that starts with 'NVAR'. If the format is
      'NVAR_TABLENAME', then 'TABLENAME' is taken to be the name of the table,
      and datalines in the table must be started with 'TABLENAME:'
    * A keyword starting with 'VARNAMES' must directly follow the
      'NVAR_TABLENAME' definition. 

    An example:

        NAME: mytables
        NVAR_MYTABLE: 3
        VARNAMES: A B C
        MYTABLE: 1 2.0 x
        MYTABLE: 4 5.0 y
        MYTABLE: 5 8.2 z


    Examples
    --------
    If this is contained in the file 'data.txt'

    >>> meta, tables = read_rk('data.txt')
    >>> meta
    OrderedDict([('NAME', 'mytables')])
    >>> tables['MYTABLE']
    array([(1, 2.0, 'x'), (4, 5.0, 'y'), (5, 8.2, 'z')], 
          dtype=[('A', '<i8'), ('B', '<f8'), ('C', '|S1')])
    >>> tables['MYTABLE']['A']
    array([1, 4, 5])

    """

    meta = odict() # initialize structure to hold metadata.
    tables = {} # initialize structure to hold data.

    infile = open(fname, 'r')
    words = infile.read().split()
    infile.close()
    i = 0
    nvar = None
    tablename = None
    while i < len(words):
        word = words[i]

        # If the word starts with 'NVAR', we are starting a new table.
        if word.startswith('NVAR'):
            nvar = int(words[i + 1])

            #Infer table name. The name will be used to designate a data row.
            if '_' in word:
                pos = word.find('_') + 1
                tablename = word[pos:].rstrip(':')
            elif default_tablename is not None:
                tablename = default_tablename
            else:
                raise ValueError(
                    'table name must be given as part of NVAR keyword so '
                    'that rows belonging to this table can be identified. '
                    'Alternatively, supply the default_tablename keyword.')
            table = odict()
            tables[tablename] = table

            i += 2

        # If the word starts with 'VARNAMES', the following `nvar` words
        # define the column names of the table.
        elif word.startswith('VARNAMES'):

            # Check that nvar is defined and that no column names are defined
            # for the current table.
            if nvar is None or len(table) > 0:
                raise Exception('NVAR must directly precede VARNAMES')

            # Read the column names
            for j in range(i + 1, i + 1 + nvar):
                table[words[j]] = []
            i += nvar + 1

        # If the word matches the current tablename, we are reading a data row.
        elif word.rstrip(':') == tablename:
            for j, colname in enumerate(table.keys()):
                table[colname].append(words[i + 1 + j])
            i += nvar + 1
        
        # Otherwise, we are reading metadata or some comment
        # If the word ends with ":", it is metadata.
        elif word[-1] == ':':
            name = word[:-1]  # strip off the ':'
            if len(words) >= i + 2:
                try:
                    val = int(words[i + 1])
                except ValueError:
                    try:
                        val = float(words[i + 1])
                    except ValueError:
                        val = words[i + 1]
                meta[name] = val
            else: 
                meta[name] = None
            i += 2
        else:
            # It is some comment; continue onto next word.
            i += 1

    # All values in each column are currently strings. Convert to int or
    # float if possible.
    for table in tables.values():
        for colname, values in table.iteritems():
            try:
                table[colname] = [int(val) for val in values]
            except ValueError:
                try:
                    table[colname] = [float(val) for val in values]
                except ValueError:
                    pass

    # Convert tables to ndarrays, if requested.
    if array:
        for tablename in tables.keys():
            tables[tablename] = dict_to_array(tables[tablename])

    return meta, tables


def read_rk_multi(fnames, default_tablename=None, array=True):
    """Like ``read_rk()``, but read from multiple files containing
    the same tables and glue results together into big tables.

    Parameters
    ----------
    fnames : list of str
        List of filenames.

    Returns
    -------
    tables : dictionary of `numpy.ndarray`s or dictionaries.

    Notes
    -----
    Reading a lot of large text files in Python can become slow. Try caching
    results with cPickle, numpy.save, numpy.savez if it makes sense for your
    application.

    Examples
    --------
    >>> tables = read_rk_multi(['data.txt', 'data.txt'])
    >>> tables.keys()
    ['MYTABLE']
    >>> tables['MYTABLE'].dtype.names
    ('A', 'B', 'C')
    >>> tables['MYTABLE']['A']
    array([1, 4, 5, 1, 4, 5])
   
"""

    compiled_tables = {}
    for fname in fnames:

        meta, tables = read_rk(fname, default_tablename=default_tablename,
                               array=False)
        for key, table in tables.iteritems():

            # If we already have a table with this key,
            # append this table to it.
            if key in compiled_tables:
                colnames = compiled_tables[key].keys()
                for colname in colnames:
                    compiled_tables[key][colname].extend(table[colname])

            # Otherwise, start a table
            else:
                compiled_tables[key] = table

    if array:
        for key in compiled_tables:
            compiled_tables[key] = dict_to_array(compiled_tables[key])

    return compiled_tables
