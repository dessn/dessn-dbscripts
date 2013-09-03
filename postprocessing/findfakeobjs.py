import csv
import cx_Oracle
import numpy as np
import sys
import string
#import timeit
import time
#snwriter/sn4rite@leovip148.ncsa.uiuc.edu/desoper
#-------------------------------------------------------------------------------
# This is for debugging the script. You should disable this once everything is
# working. Output is written to $SPLUNK_HOME/var/log/splunk/python.log, which is
# searchable in splunk with:   index=_internal source=*python.log ORALOOKUP:
# Be sure to also remove/disable 'logger.info(...)' lines when you disable this.
import logging
logger = logging.getLogger("ora_lookup_example")
#-------------------------------------------------------------------------------
t1 = time.time()
FIELDS = [ "PartnerId", "CorporationName", "OrgUnitName" ]

def db_connect():
    # Build a DSN (can be subsitited for a TNS name)
    dsn = cx_Oracle.makedsn("host.example.net", 1521, "SID")
    db = cx_Oracle.connect("username", "password", dsn)
    cursor = db.cursor()
    return cursor

def desdb_connect():
    username = 'snwriter'
    password = 'sn4rite'
    dsn = 'leovip148.ncsa.uiuc.edu/desoper'
    db = cx_Oracle.connect(username, password, dsn)
    cursor = db.cursor()
    return cursor


def printf(format, *args):
    sys.stdout.write(format % args)



#def db_lookup(cursor, key):
#    cursor.execute("""
#        SELECT CORPORATIONNAME, ORGUNITNAME
#        FROM PARTNER
#        WHERE PARTNERID = :id
#          AND STATUS = 'Active'""", dict(id=key))
#    row = cursor.fetchone()
#    return row


def desdb_lookup_ralist(bin):
    username = 'snreader'
    password = 'sn4rede'
    dsn = 'leovip148.ncsa.uiuc.edu/desoper'
    db = cx_Oracle.connect(username, password, dsn)
    cursor = db.cursor()
    cursor.execute("""
         SELECT floor(ra/:x)*:x, count(*)
         FROM SNCAND where cand_type>=0 and numepochs>1 and ra<42 and ra>35 
         GROUP BY floor(ra/:x)""", x=bin)
    row = cursor.fetchall()
    cursor.close()
    db.close()
    return row

def desdb_query(query):
    #print query
    username = 'snreader'
    password = 'sn4rede'
    dsn = 'leovip148.ncsa.uiuc.edu/desoper'
    db = cx_Oracle.connect(username, password, dsn)
    cursor = db.cursor()
    #print query
    cursor.execute(query)
    row = cursor.fetchall()
    #print row
    cursor.close()
    db.close()
    return row

   
def desdb_lookup_query(cursor, query):
    cursor.execute(query)
    row = cursor.fetchall()
    cursor.close()
    return row

def desdb_update(query):
    username = 'snwriter'
    password = 'sn4rite'
    dsn = 'leovip148.ncsa.uiuc.edu/desoper'
    db = cx_Oracle.connect(username, password, dsn)
    cursor = db.cursor()
    cursor.execute(query)
    db.commit()
    cursor.close()
    db.close()
    return 0
    
bin=0.2
version1="2012_SciVer_v3"
searcharea=0.0005
racount = desdb_lookup_ralist(bin)
racount.sort()
rac = np.array(racount)
count = rac[:,1]
ralow = rac[:,0]
for i in xrange(len(count)):
#for i in xrange(50):
    start = time.time()
    print "There are {0} candidates where {1}<ra<={2}.".format(count[i], ralow[i], ralow[i]+bin)
    x="MERGE INTO SNOBS o USING ( WITH obs as (select /*+INDEX(sncand, sncand_radec_idx) materialize */  SNOBJID, status, ra, dec from SNOBS where ra>={0} and ra<{1} and status=0), ffake as ( select /*+INDEX(snfake, snfake_radec_idx) materialize */ f.ID, f.ra, f.dec from SNFAKE f where version='{3}' and ra>={0}-{2} and ra<{1}+{2}) SELECT distinct o.SNOBJID, o.status, o.ra, o.dec from obs o INNER JOIN ffake f on o.ra-f.ra>-{2}/cos(o.dec*3.14159265/180.0) and o.ra-f.ra<{2}/cos(o.dec*3.14159265/180) and o.dec-f.dec>-{2} and o.dec-f.dec<{2} ) b on (o.SNOBJID=b.SNOBJID) WHEN MATCHED THEN UPDATE set o.status=1 ".format(ralow[i], ralow[i]+bin, searcharea, version1)

    print x
    
    #x = "SELECT c.SNOBJID, c.status, f.ID, c.ra, c.dec from (select * from SNOBS where ra>={0} and ra<{1} and status=0) c join (select * from SNFAKE where ra>={0}-{2} and ra<{1}+{2}) f on abs(c.ra-f.ra)<{2} and abs(c.dec-f.dec)<{2} where f.version='{3}' order by c.SNOBJID".format(ralow[i], ralow[i]+bin, searcharea, version1)
    
    datrows = desdb_update(x)
    #if (datrows):
    #    datrows.sort()
    #    rows = np.array(datrows)
    #    SNID = rows[:,0]
    #    FAKEID = rows[:,1]
    #    FFID = rows[:,2]
    #    RA = rows[:,3]
    #    DEC = rows[:,4]
    #    for j in xrange(len(SNID)):
    #        if FAKEID[j]==0:
    #            print int(SNID[j]), int(FAKEID[j]), int(FFID[j]), RA[j], DEC[j]
    #            x="UPDATE SNOBS SET status=1 where SNOBJID={0}".format(int(SNID[j]))
    #            desdb_update(x)
        
    end = time.time()
    print "Query took ", end-start, "seconds."
    print " "
t2 = time.time()

print "Whole job took ", t2-t1, "seconds."
