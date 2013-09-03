import csv
import cx_Oracle
import numpy as np
import sys
import string
import time
import getopt

#-------------------------------------------------------------------------------
# This is for debugging the script. You should disable this once everything is
# working. Output is written to $SPLUNK_HOME/var/log/splunk/python.log, which is
# searchable in splunk with:   index=_internal source=*python.log ORALOOKUP:
# Be sure to also remove/disable 'logger.info(...)' lines when you disable this.
import logging
logger = logging.getLogger("ora_lookup_example")
#-------------------------------------------------------------------------------
t1 = time.time()


def desdb_connect():
    username = 'accountwiththeabilitytowrite'
    password = 'passwordfortheaboveaccount'
    dsn = 'hostfortheaboveaccount'
    db = cx_Oracle.connect(username, password, dsn)
    cursor = db.cursor()
    return cursor

def getcommandarg(beg, en):
    return

def printf(format, *args):
    sys.stdout.write(format % args)


#The below 
def field_limits(field):
    username = 'accountwiththeabilitytowrite'
    password = 'passwordfortheaboveaccount'
    dsn = 'hostfortheaboveaccount'
    db = cx_Oracle.connect(username, password, dsn)
    cursor = db.cursor()
    cursor.execute("""
         SELECT min(o.ra) mnra, max(o.ra) mxra, min(o.dec) mndec, max(o.dec) mxdec
         FROM SNOBS o JOIN EXPOSURE e on o.EXPOSUREID=e.EXPNUM
         WHERE STATUS>=0 and substr(e.OBJECT,22,2)='{0}'
         """.format(field))
    row = cursor.fetchall()
    cursor.close()
    db.close()
    return row

def desdb_lookup_ralist(bin, ralow, rahi, declow, dechi):
    username = 'accountwiththeabilitytowrite'
    password = 'passwordfortheaboveaccount'
    dsn = 'hostfortheaboveaccount'
    db = cx_Oracle.connect(username, password, dsn)
    cursor = db.cursor()
    cursor.execute("""
         SELECT floor(ra/:x)*:x, count(*)
         FROM SNCAND
         WHERE ra>=:rl and ra<=:rh and dec>=:dl and dec<=:dh
         GROUP BY floor(ra/:x)""", x=bin, rl=ralow, rh=rahi, dl=declow, dh=dechi)
    row = cursor.fetchall()
    cursor.close()
    db.close()
    return row

def desdb_update_query(query):
    #print query
    username = 'accountwiththeabilitytowrite'
    password = 'passwordfortheaboveaccount'
    dsn = 'hostfortheaboveaccount'
    db = cx_Oracle.connect(username, password, dsn)
    cursor = db.cursor()
    print "THE UPDATE"
    cursor.execute(query)
    print "EXECUTED"
    db.commit()
    cursor.close()
    db.close()
    return 0
   
def desdb_lookup_query(query):
    username = 'accountwiththeabilitytowrite'
    password = 'passwordfortheaboveaccount'
    dsn = 'hostfortheaboveaccount'
    db = cx_Oracle.connect(username, password, dsn)
    cursor = db.cursor()
    cursor.execute(query)
    row = cursor.fetchall()
    cursor.close()
    db.close()
    return row

count=[]
bin=0.1
searcharea=1.0/3600.0
farea = 0.0005


field=sys.argv[1]


lims = field_limits(field)
limsac = np.array(lims)

rlow=limsac[0][0]
rhi=limsac[0][1]

dlow=limsac[0][2]
dhi=limsac[0][3]
print rlow, rhi, dlow, dhi, float(int(rlow/bin))*bin
racount = desdb_lookup_ralist(bin, rlow, rhi, dlow, dhi)
racount.sort()
rac = np.array(racount)
count = rac[:,1]
ralow = rac[:,0]

nus="num_unscanned"
nr="num_real"
na="num_artifact"


#xn1 = "update SNCAND set num_real=0 where num_real is null and ra>={0} and ra<={1}".format(ralow, rahi)
#xn2 = "update SNCAND set num_artifact=0 where num_artifact is null and ra>={0} and ra<={1}".format(ralow, rahi)
#xn3 = "update SNCAND set num_unscanned=0 where num_unscanned is null and ra>={0} and ra<={1}".format(ralow, rahi)
#desdb_update_query(xn1)
#desdb_update_query(xn2)
#desdb_update_query(xn3)


for i in xrange(len(count)):
    print ralow[i], count[i], i
    start = time.time()
    if (ralow[i]>=rlow-bin and ralow[i]<=rhi):
        print "There are {0} candidates where {1}<ra<={2}.".format(count[i], ralow[i], ralow[i]+bin)


        x = "        MERGE INTO SNCAND ca  USING (    WITH cands as    (    select /*+INDEX(sncand, sncand_radec_idx) materialize */             snid, ra, dec        from sncand   where ra >= {0}             AND ra < {1}      and cand_type>=0       AND dec >= {3}             AND dec <= {4}    ),    obs as    (    select /*+index(snobs, SNOBS_RADEC_IDX) materialize */            mag, ra, dec, exposureid        from SNOBS        where status = 0             AND ra > {0} - {2}             AND ra <= {1} + {2}             AND dec >= {3} - {2}             AND dec <= {4} + {2}    and object_type={6}   and flux/flux_err>4.0 )    SELECT   a.SNID,             MIN (o.mag) peak_mag,             COUNT (DISTINCT e.NITE) numepochs,             COUNT (DISTINCT CONCAT (e.BAND, E.NITE)) numobs,             SUM (o.ra) / COUNT (*) ra,             SUM (o.dec) / COUNT (*) dec,             TO_CHAR (MAX (TO_NUMBER (e.nite))) nitee        FROM cands a             INNER JOIN             obs o                ON     a.ra - o.ra > -{2}/cos(a.dec*3.14159265/180)                   AND a.ra - o.ra < {2}/cos(a.dec*3.14159265/180)                   AND a.dec - o.dec > -{2}                   AND a.dec - o.dec < {2}             INNER JOIN EXPOSURE e ON e.EXPNUM = o.EXPOSUREID    GROUP BY a.SNID  ) b ON (ca.SNID = b.SNID) WHEN MATCHED THEN   UPDATE SET ca.{5} = b.numobs ".format(ralow[i], ralow[i]+bin, searcharea, dlow, dhi, "num_unscanned", 0)

   
        x2 = "        MERGE INTO SNCAND ca  USING (    WITH cands as    (    select /*+INDEX(sncand, sncand_radec_idx) materialize */             snid, ra, dec        from sncand   where ra >= {0}             AND ra < {1}       and cand_type>=0      AND dec >= {3}             AND dec <= {4}    ),    obs as    (    select /*+index(snobs, SNOBS_RADEC_IDX) materialize */            mag, ra, dec, exposureid        from SNOBS        where status = 0             AND ra > {0} - {2}             AND ra <= {1} + {2}             AND dec >= {3} - {2}             AND dec <= {4} + {2}    and object_type={6}   and flux/flux_err>4.0 )    SELECT   a.SNID,             MIN (o.mag) peak_mag,             COUNT (DISTINCT e.NITE) numepochs,             COUNT (DISTINCT CONCAT (e.BAND, E.NITE)) numobs,             SUM (o.ra) / COUNT (*) ra,             SUM (o.dec) / COUNT (*) dec,             TO_CHAR (MAX (TO_NUMBER (e.nite))) nitee        FROM cands a             INNER JOIN             obs o                ON     a.ra - o.ra > -{2}/cos(a.dec*3.14159265/180)                   AND a.ra - o.ra < {2}/cos(a.dec*3.14159265/180)                   AND a.dec - o.dec > -{2}                   AND a.dec - o.dec < {2}             INNER JOIN EXPOSURE e ON e.EXPNUM = o.EXPOSUREID    GROUP BY a.SNID  ) b ON (ca.SNID = b.SNID) WHEN MATCHED THEN   UPDATE SET  ca.{5} = b.numobs".format(ralow[i], ralow[i]+bin, searcharea, dlow, dhi, "num_real", 1)
        

        x3 = "        MERGE INTO SNCAND ca  USING (    WITH cands as    (    select /*+INDEX(sncand, sncand_radec_idx) materialize */             snid, ra, dec        from sncand   where ra >= {0}             AND ra < {1}       and cand_type>=0      AND dec >= {3}             AND dec <= {4}    ),    obs as    (    select /*+index(snobs, SNOBS_RADEC_IDX) materialize */            mag, ra, dec, exposureid        from SNOBS        where status = 0             AND ra > {0} - {2}             AND ra <= {1} + {2}             AND dec >= {3} - {2}             AND dec <= {4} + {2}    and object_type={6}   and flux/flux_err>4.0 )    SELECT   a.SNID,             MIN (o.mag) peak_mag,             COUNT (DISTINCT e.NITE) numepochs,             COUNT (DISTINCT CONCAT (e.BAND, E.NITE)) numobs,             SUM (o.ra) / COUNT (*) ra,             SUM (o.dec) / COUNT (*) dec,             TO_CHAR (MAX (TO_NUMBER (e.nite))) nitee        FROM cands a             INNER JOIN             obs o                ON     a.ra - o.ra > -{2}/cos(a.dec*3.14159265/180)                   AND a.ra - o.ra < {2}/cos(a.dec*3.14159265/180)                   AND a.dec - o.dec > -{2}                   AND a.dec - o.dec < {2}             INNER JOIN EXPOSURE e ON e.EXPNUM = o.EXPOSUREID    GROUP BY a.SNID  ) b ON (ca.SNID = b.SNID) WHEN MATCHED THEN   UPDATE SET            ca.{5} = b.numobs".format(ralow[i], ralow[i]+bin, searcharea, dlow, dhi, "num_artifact", 16)

        
        x4 = "        MERGE INTO SNCAND ca  USING (    WITH cands as    (    select /*+INDEX(sncand, sncand_radec_idx) materialize */             snid, ra, dec        from sncand   where ra >= {0}       and cand_type>=0      AND ra < {1}             AND dec >= {3}             AND dec <= {4}    ),    obs as    (    select /*+index(snobs, SNOBS_RADEC_IDX) materialize */            mag, ra, dec, exposureid        from SNOBS        where status = 0             AND ra > {0} - {2}             AND ra <= {1} + {2}             AND dec >= {3} - {2}             AND dec <= {4} + {2}    and object_type={6}   and flux/flux_err>4.0 )    SELECT   a.SNID,             MIN (o.mag) peak_mag,             COUNT (DISTINCT e.NITE) numepochs,             COUNT (DISTINCT CONCAT (e.BAND, E.NITE)) numobs,             SUM (o.ra) / COUNT (*) ra,             SUM (o.dec) / COUNT (*) dec,             TO_CHAR (MAX (TO_NUMBER (e.nite))) nitee        FROM cands a             INNER JOIN             obs o                ON     a.ra - o.ra > -{2}/cos(a.dec*3.14159265/180)                   AND a.ra - o.ra < {2}/cos(a.dec*3.14159265/180)                   AND a.dec - o.dec > -{2}                   AND a.dec - o.dec < {2}             INNER JOIN EXPOSURE e ON e.EXPNUM = o.EXPOSUREID    GROUP BY a.SNID  ) b ON (ca.SNID = b.SNID) WHEN MATCHED THEN   UPDATE SET            ca.{5} = b.numobs".format(ralow[i], ralow[i]+bin, searcharea, dlow, dhi, "num_unsure", 32)
        
        x5 = "        MERGE INTO SNCAND ca  USING (    WITH cands as    (    select /*+INDEX(sncand, sncand_radec_idx) materialize */             snid, ra, dec        from sncand   where ra >= {0}      and cand_type>=0       AND ra < {1}             AND dec >= {3}             AND dec <= {4}    ),    obs as    (    select /*+index(snobs, SNOBS_RADEC_IDX) materialize */            mag, ra, dec, exposureid        from SNOBS        where status = 0             AND ra > {0} - {2}             AND ra <= {1} + {2}             AND dec >= {3} - {2}             AND dec <= {4} + {2}   and flux/flux_err>4.0 )    SELECT   a.SNID,             MIN (o.mag) peak_mag,             COUNT (DISTINCT e.NITE) numepochs,             COUNT (DISTINCT CONCAT (e.BAND, E.NITE)) numobs,             SUM (o.ra) / COUNT (*) ra,             SUM (o.dec) / COUNT (*) dec,             TO_CHAR (MAX (TO_NUMBER (e.nite))) nitee        FROM cands a             INNER JOIN             obs o                ON     a.ra - o.ra > -{2}/cos(a.dec*3.14159265/180)                   AND a.ra - o.ra < {2}/cos(a.dec*3.14159265/180)                   AND a.dec - o.dec > -{2}                   AND a.dec - o.dec < {2}             INNER JOIN EXPOSURE e ON e.EXPNUM = o.EXPOSUREID    GROUP BY a.SNID  ) b ON (ca.SNID = b.SNID) WHEN MATCHED THEN   UPDATE SET ca.peak_mag = b.peak_mag,              ca.numepochs = b.numepochs,              ca.{5} = b.numobs,              ca.ra = b.ra,              ca.dec = b.dec,              ca.latest_nite = b.nitee".format(ralow[i], ralow[i]+bin, searcharea, dlow, dhi, "numobs")


       
        desdb_update_query(x)
        
        desdb_update_query(x2)
       
        desdb_update_query(x3)
        
        desdb_update_query(x4)
       
        desdb_update_query(x5)

#NOW FOR FAKE FINDING

        x = "SELECT c.SNID, c.SNFAKE_ID, f.ID, c.ra, c.dec from (select * from SNCAND where ra>={0} and ra<{1} and cand_type>=0) c join (select * from SNFAKE where ra>={0}-{2} and ra<{1}+{2}) f on abs(c.ra-f.ra)<{2} and abs(c.dec-f.dec)<{2} where f.version='2012_SciVer_v3' order by c.SNID".format(ralow[i], ralow[i]+bin, farea)
    
        datrows = desdb_lookup_query(x)
        if (datrows):
            datrows.sort()
            rows = np.array(datrows)
            SNIDf = rows[:,0]
            FAKEIDf = rows[:,1]
            FFIDf = rows[:,2]
            RAf = rows[:,3]
            DECf = rows[:,4]
            for j in xrange(len(SNIDf)):
                if FAKEIDf[j]==0:
                    print int(SNIDf[j]), int(FAKEIDf[j]), int(FFIDf[j]), RAf[j], DECf[j]
                    x="UPDATE SNCAND SET SNFAKE_ID={0} where SNID={1}".format(int(FFIDf[j]), int(SNIDf[j]))
                    desdb_update_query(x)
        
        end = time.time()
        print "Query took ", end-start, "seconds."
x5 = "update SNCAND set numobs=num_unscanned+num_real+num_artifact where num_unscanned is not null and num_real is not null and num_artifact is not null"
#desdb_update_query(x5)
print " "
t2 = time.time()

print "Whole job took ", t2-t1, "seconds in field ", field
