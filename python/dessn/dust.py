import urllib
from xml.dom.minidom import parse

__all__ = ['mwdust']

IRSA_BASE_URL = \
    'http://irsa.ipac.caltech.edu/cgi-bin/DUST/nph-dust?locstr={:.5f}+{:.5f}'

def mwdust(ra, dec):
    """Return Milky Way E(B-V) at given coordinates using a web query of the
    IRSA Schlegel dust map calculator.

    Parameters
    ----------
    ra, dec : float
        RA and Dec in degrees.

    Returns
    -------
    mwebv : float
        E(B-V) at given location.
    """

    u = urllib.urlopen(IRSA_BASE_URL.format(ra, dec))
    if not u:
        raise ValueError('URL query returned false')
    dom = parse(u)
    u.close()

    ebvstr = dom.getElementsByTagName('meanValue')[0].childNodes[0].data
    result = float(ebvstr.strip().split()[0])

    return result
