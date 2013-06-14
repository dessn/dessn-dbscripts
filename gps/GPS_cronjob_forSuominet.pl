#!/usr/bin/perl
#
# Created Jan 2013 by R.Kessler
#
#
# ./GPS_cronjob_forSuominet.pl
# ./GPS_cronjob_forSuominet.pl IGNORE     (use IGNORE prefix)
# ./GPS_cronjob_forSuominet.pl NOCLEAN
#
# (script can be launched from any FNAL-DES machine since full paths are used)
#
# If New GPS data files are found, then pull weather file(s) 
# from web and ftp everything to Suominet.
# Note that this script can run ONLY from FNAL because
#   1. GPS files are automatically ftp'ed to FNAL
#   2. Suominet allows ftp transfer only from FNAL
#
# ----------------------------------

use Time::Local ;
use strict ;


#my $SNDATA_ROOT   = $ENV{'SNDATA_ROOT'};
my $SNDATA_ROOT   = "/data/dp62.b/data/SNDATA_ROOT" ;

my $TOPDIR_GPS    = "$SNDATA_ROOT/INTERNAL/DES/GPS" ;
my $DIR_TRIMBLE   = "$TOPDIR_GPS/DATA_TrimbleGPS"; # raw data from CTIO
my $DIR_WEATHER   = "$TOPDIR_GPS/DATA_weather" ;   # CTIO weather files
my $SDIR_SUOMINET = "DATA_Suominet_ftp" ; 
my $DIR_SUOMINET  = "$TOPDIR_GPS/$SDIR_SUOMINET" ;  # files ready for ftp
my $DIR_CRONLOG   = "$TOPDIR_GPS/cronLogs" ;   # CTIO weather files
my $DIR_ARCHIVE   = "$TOPDIR_GPS/DATA_Suominet_sent" ; 

my $cdLOG = "cd $DIR_CRONLOG";

my $SERIAL_NUMBER     = "5213K83432" ;
my $LEN_SNUM          = length($SERIAL_NUMBER); 
my $LEN_DATE          = 8 ;  # YYYYMMDD
my $SUOMINET_PREFIX   = "CTIO" ;
my $SUFFIX_GPSDATA    = "T02";
my $SUFFIX_WEATHER    = "met";

my $URL_weather = 
    "http://www.ctio.noao.edu/noao/sites/default/files/CTIOweather" ;
my $URL_weatherList =  
    "$URL_weather/list.txt" ;


my $CLEANFLAG = 1;

# --- ftp declarations -----

use Net::FTP;
my $ftp_host = "suomildm1.cosmic.ucar.edu";
my $ftp_user = "uchicago" ;
my $ftp_pw   = "U12548\$\#" ;



# ------ misc. globals -------
my ($CDATE_PROC, $LOGFILE, @MSGERR );
my ($weatherList, @weatherFiles ) ;
my ($NFILE_GPSDATA, @GPSDATA_LIST_FILE, @GPSDATA_LIST_DATE);
my (@GPSDATA_LIST_YEAR, @GPSDATA_LIST_MONTH, @GPSDATA_LIST_DAY);
my (@GPSDATA_LIST_SuomiDATE );

my (@Suominet_LIST_WEATHERFILE);
my (@Suominet_LIST_GPSFILE);


# ------------------------------------
# function declarations
sub checkDIRs ;
sub parse_args ;
sub openLog ;
sub check_newGPSDATA_FILES ;
sub check_CTIOweather_FILES ;
sub make_SuominetWeather_FILES ;
sub addDaysToDate ;
sub moveFILES_to_ftpDir ;
sub exec_ftp ;
sub archive_FILES ;
sub clean_FILES ;
sub ftp_Test ;

# ========== BEGIN MAIN ===============


# &ftp_Test();

&parse_args();

&openLog();

&checkDIRs();  # make sure required directories exist.

&check_newGPSDATA_FILES();

&check_CTIOweather_FILES();

&make_SuominetWeather_FILES();

&moveFILES_to_ftpDir();

&exec_ftp();

&archive_FILES();

&clean_FILES();

print PTR_LOGFILE "\n DONE. \n";

# =========== END OF MAIN =============


### ----------------------------------
sub checkDIRs {

    my ($DIR);
    my @DIRLIST  = ( "$DIR_TRIMBLE", "$DIR_WEATHER", "$DIR_SUOMINET", 
		     "$DIR_CRONLOG", "$DIR_ARCHIVE" );

    foreach $DIR ( @DIRLIST ) {
	if ( -d $DIR ) {
	    print PTR_LOGFILE "Found required directory: \n   $DIR \n" ; 
	}
	else {
	    $MSGERR[0] = "Could not find required directory";
	    $MSGERR[1] = "$DIR" ;
	    &FATAL_ERROR();
	}
    }

    print PTR_LOGFILE 
	"# ----------------------------------------------------------\n\n" ;

}   # end of checkDIRs

### ---------------------------
sub ftp_Test {

    my ($f, $ISTAT);

    print " **** FTP TESTS  **** \n";

    $f = Net::FTP->new($ftp_host) ;
    print "\t FTP->new : f = '$f' \n";

    $ISTAT = ($f->login($ftp_user,$ftp_pw));
    print "\t login : f = '$f' (ISTAT = $ISTAT) \n" ;


    my (@FILELIST, $file, @FILE_ls );    

   @FILELIST = qx(cd $DIR_SUOMINET ; ls ${SUOMINET_PREFIX}*);


    foreach $file ( @FILELIST ) {
	$file =~ s/\s+$// ;   # trim trailing whitespace
	$ISTAT = $f->put("$DIR_SUOMINET/$file") ;
	print "\t ->put $file  (ISTAT = '$ISTAT') \n";
    }

    @FILE_ls = $f->ls ;

    foreach $file ( @FILE_ls ) {
	$file =~ s/\s+$// ;   # trim trailing whitespace
	print "\t Found Suominet file: $file \n";
    }


    die "\n xxxx DIE OK xxxx \n";
}


### ------------------------
sub parse_args {

    my ($NARG, $i );

    $NARG = scalar(@ARGV);
    if ( $NARG <= 0 ) { return ; }

    for ( $i=0 ; $i < $NARG ; $i++ ) {
	if ( $ARGV[$i] eq "IGNORE"  ) { $SUOMINET_PREFIX   = "IGNORE_CTIO" ; }
	if ( $ARGV[$i] eq "NOCLEAN" ) { $CLEANFLAG = 0 ; }
    }

} # end of parse_args


### --------------------------------
sub openLog {

    # open log file to record what happens.

    my $cdate ;

    $CDATE_PROC         = `date +%y%m%d_%H%M` ;
    $CDATE_PROC         =~ s/\s+$// ;   # trim trailing whitespace
    $LOGFILE = "$DIR_CRONLOG/GPS_cronjob_${CDATE_PROC}.LOG" ;

#    print " Open LOGFILE: \n  $LOGFILE \n\n";
    $cdate = `date` ;
    $cdate =~ s/\s+$// ;   # trim trailing whitespace

    open  PTR_LOGFILE , "> $LOGFILE" ;   
    print PTR_LOGFILE "Execute $0\n";
    print PTR_LOGFILE "$cdate \n\n";

} # end of openLog


### --------------------------------
sub check_newGPSDATA_FILES {

    # check for new GPS data files in  $DIR_TRIMBLE.
    # For each file, determine date from filename.
    # Note that nothing gets moved or ftp'ed until
    # after the weather data is found.

    my ($GPSFILE, $GPSDATE, $j, $ifile, $YYYY, $MM, $DD );

    @GPSDATA_LIST_FILE = 
	qx(cd $DIR_TRIMBLE ;  ls *$SUFFIX_GPSDATA 2>/dev/null);
    $NFILE_GPSDATA = scalar(@GPSDATA_LIST_FILE);

    print PTR_LOGFILE " Found $NFILE_GPSDATA new GPS data files in \n" ;
    print PTR_LOGFILE " $DIR_TRIMBLE \n";

    if ( $NFILE_GPSDATA == 0 ) {
	print PTR_LOGFILE "\n Quitting GRACEFULLY. \n";
	exit(1) ;
    }

    # figure out date for each file.
    $ifile = 0 ;

    foreach $GPSFILE ( @GPSDATA_LIST_FILE ) {
	$GPSFILE  =~ s/\s+$// ;   # trim trailing whitespace

	$j = index($GPSFILE,$SERIAL_NUMBER);
	if ( $j < 0 ) {
	    $MSGERR[0] = "Invalid GPS filename : '$GPSFILE'";
	    $MSGERR[1] = "File name expected to start with ";
	    $MSGERR[2] = "serial number = '$SERIAL_NUMBER'";
	    &FATAL_ERROR();
	}

	$GPSDATE = substr($GPSFILE,$LEN_SNUM,$LEN_DATE);
	@GPSDATA_LIST_DATE[$ifile] = "$GPSDATE" ;


	$YYYY = substr($GPSDATE,0,4);
	$MM   = substr($GPSDATE,4,2);
	$DD   = substr($GPSDATE,6,2);

	$GPSDATA_LIST_YEAR[$ifile]  = $YYYY ; 
	$GPSDATA_LIST_MONTH[$ifile] = int($MM) ;
	$GPSDATA_LIST_DAY[$ifile]   = int($DD) ;
	$GPSDATA_LIST_SuomiDATE[$ifile] = "${GPSDATE}" ;

	$Suominet_LIST_GPSFILE[$ifile] =
	    "${SUOMINET_PREFIX}_${GPSDATE}.${SUFFIX_GPSDATA}" ;

	print PTR_LOGFILE "\t ($ifile) $GPSFILE  (date=$YYYY-$MM-$DD) \n";
	$ifile++ ;
    }


} # end of check_newGPSDATA_FILES



### ---------------------------------------------
sub check_CTIOweather_FILES {

    my ($YYYY, $MM, $DD,  $yyyy, $mm, $dd, $ifile );
    my ($CDATE, $cdate, $GPSFILE, $WFILE, $wfile  );
    my $q = "'" ;

    print PTR_LOGFILE "\n Check weather files.\n";

    # first download master list of all weather files.
    $weatherList = "weatherList_${CDATE_PROC}.txt" ;
    qx($cdLOG; wget $URL_weatherList 2>/dev/null ; mv list.txt $weatherList);

    foreach ( $ifile=0; $ifile < $NFILE_GPSDATA; $ifile++ ) {

	$GPSFILE = $GPSDATA_LIST_FILE[$ifile] ; 
	$YYYY = $GPSDATA_LIST_YEAR[$ifile]  ; 
	$MM   = $GPSDATA_LIST_MONTH[$ifile] ;
	$DD   = $GPSDATA_LIST_DAY[$ifile]  ;

	# find date from day before too.
	($yyyy, $mm, $dd)  = addDaysToDate($YYYY, $MM, $DD, -1);

	# Mar 15 2013: use 2-digit month and day since Walker changed the URL
	$DD = sprintf("%2.2d", $DD ) ;
	$dd = sprintf("%2.2d", $dd ) ;
	$MM = sprintf("%2.2d", $MM ) ;
	$mm = sprintf("%2.2d", $mm ) ;

	$CDATE = "$YYYY-$MM-$DD" ;
	$cdate = "$yyyy-$mm-$dd" ;

	print PTR_LOGFILE "   ($ifile) $GPSFILE : \n" ;

	$WFILE = qx($cdLOG ; grep $q$CDATE$q $weatherList 2>/dev/null ) ;
	$wfile = qx($cdLOG ; grep $q$cdate$q $weatherList 2>/dev/null ) ;

	if ( length($WFILE) > 0 ) {
	    my $j  = rindex($WFILE,'/');
	    $WFILE = substr($WFILE,$j+1,40);
	    $WFILE =~ s/\s+$// ;   # trim trailing whitespace
	    print PTR_LOGFILE "\t Found required  $WFILE \n";
	}
	else {
	    $MSGERR[0] = "Could not find required weather file for";
	    $MSGERR[1] = "'$CDATE' in $weatherList" ;
	    &FATAL_ERROR();
	}

	if ( length($wfile) > 0 ) {
	    my $j  = rindex($wfile,'/');
	    $wfile  = substr($wfile,$j+1,40);
	    $wfile  =~ s/\s+$// ;   # trim trailing whitespace
	    print PTR_LOGFILE "\t Found required  $wfile \n";
	}
	else {
	    $MSGERR[0] = "Could not find required weather file for";
	    $MSGERR[1] = "'$cdate' in $weatherList";
	    &FATAL_ERROR();
	}

	$weatherFiles[$ifile] = "$wfile $WFILE" ;

	# get weather files from web
	# only 'wget' the one(s) that don't exist.

	if ( !(-e "$DIR_CRONLOG/$wfile" ) )
	{  qx($cdLOG ; wget $URL_weather/$wfile 2>/dev/null ); }

	if ( !(-e "$DIR_CRONLOG/$WFILE" ) )
	{ qx($cdLOG ; wget $URL_weather/$WFILE 2>/dev/null ); }

	# --------------------------------------------
	# ERROR CHECK: make sure that both files exist.
	if ( !(-e "$DIR_CRONLOG/$wfile" ) )   { 
	    $MSGERR[0] = "Expected weather file '$wfile' does not exist" ;
	    $MSGERR[1] = "Check $URL_weather/$wfile" ;
	    &FATAL_ERROR();
	}

	if ( !(-e "$DIR_CRONLOG/$WFILE" ) )   { 
	    $MSGERR[0] = "Expected weather file '$WFILE' does not exist" ;
	    $MSGERR[1] = "Check $URL_weather/$WFILE" ;
	    &FATAL_ERROR();
	}

    }  # end of $ifile loop

} # end of check_CTIOweather_FILES {



### ------------------------------------------------
sub addDaysToDate {

    # pulled this function from the internet:
    # http://www.borngeek.com/2009/04/22/replacement-for-add_delta_days/

    my ($y, $m, $d, $offset) = @_;

    # Convert the incoming date to epoch seconds
    my $TIME = timelocal(0, 0, 0, $d, $m-1, $y-1900);

    # Convert the offset from days to seconds and add
    # to our epoch seconds value
    $TIME += 60 * 60 * 24 * $offset;

    # Convert the epoch seconds back to a legal 'calendar date'
    # and return the date pieces
    my @values = localtime($TIME);
    return ($values[5] + 1900, $values[4] + 1, $values[3]);

}  # addDaysToDate 


### ---------------------------------
sub make_SuominetWeather_FILES {

    # read weather files for GPS date and day before,
    # then grab all UT dates and put them into a single
    # Suominet weather file.  The CTIO weather files are
    # open/closed at midnight local time that does not 
    # overlap UT. The Suominet weather file goes from 
    # midnight to midnight in UT.

    my ($ifile, $list, @WEATHERLINES, $WFILE, $SuomiDATE, $DATE_UT );
    my ($YYYY, $MM, $DD, $DATE_UT, $line, @wdlist );
    my ($minute, $Temp, $Pres, $Humid );

#    print "\n Make Suominet weather files:  \n";
    print PTR_LOGFILE "\n Make Suominet weather files:  \n";

    for ( $ifile=0; $ifile < $NFILE_GPSDATA; $ifile++ ) {

	# construct date-string to extract
	$YYYY = $GPSDATA_LIST_YEAR[$ifile] ;	
	$MM   = $GPSDATA_LIST_MONTH[$ifile] ;	
	$DD   = $GPSDATA_LIST_DAY[$ifile] ;	
	$DATE_UT = sprintf("%4d-%2.2d-%2.2d", $YYYY, $MM, $DD );

	# construct name of weather file for Suominet
	$SuomiDATE = $GPSDATA_LIST_SuomiDATE[$ifile] ;
	$WFILE = "${SUOMINET_PREFIX}_${SuomiDATE}.$SUFFIX_WEATHER" ;
	$Suominet_LIST_WEATHERFILE[$ifile] = $WFILE ; 
	open PTR_WFILE , "> $DIR_WEATHER/$WFILE" ;

	$list = $weatherFiles[$ifile] ;
	@WEATHERLINES = qx($cdLOG ; cat $list);

#	print " xxx $list -> $WFILE ($DATE_UT) \n";

	foreach $line (@WEATHERLINES) {
	    @wdlist = split(/\s+/,$line);
	    if(  $wdlist[0] ne $DATE_UT ) { next ; }
	    
	    $minute = $wdlist[1] ;
	    $Temp   = $wdlist[2] ;
	    $Humid  = $wdlist[3] ;
	    $Pres   = $wdlist[4] ;

	    print PTR_WFILE "$DATE_UT $minute  $Temp  $Pres \n";
	}
	close PTR_WFILE ;
	print PTR_LOGFILE "\t ($ifile) Created $WFILE for Suominet. \n";
    }


} # end of make_SuominetWeather_FILES 


### ----------------------------------
sub moveFILES_to_ftpDir {

    # move GPS & weather file(s) to ftp directory

    my ($ifile, $G0, $G1, $W, $mv );

#    print "\n Move files to ftp dir.\n";

    print PTR_LOGFILE "\n" ;
    print PTR_LOGFILE " Move files to ftp directory\n\t $DIR_SUOMINET \n" ;

    for ( $ifile=0; $ifile < $NFILE_GPSDATA ; $ifile++ ) {
	$G0 = $GPSDATA_LIST_FILE[$ifile] ;         # original GPS file name
	$G1 = $Suominet_LIST_GPSFILE[$ifile];      # name for Suominet
	$W  = $Suominet_LIST_WEATHERFILE[$ifile];  # weather file


	$mv = "mv $DIR_TRIMBLE/$G0  $DIR_SUOMINET/$G1";
	print PTR_LOGFILE "\t $mv  \n";
	qx($mv);

	$mv = "mv $DIR_WEATHER/$W  $DIR_SUOMINET/";
	print PTR_LOGFILE "\t $mv  \n";
	qx($mv);
    }

} # end of moveFILES_to_ftpDir


### ---------------------------
sub exec_ftp {

    # Execute ftp for each GPS and weather file.
    # After ftp, do a remote 'ls' and verifty that
    # each file is actually there at Suominet.

    my ($f, $STAT);
    my (@FILELIST, $file, $file2, @FILE_ls, $NERR, $FOUND );

    print PTR_LOGFILE "\n ftp files to $ftp_host \n" ;

    
    # make connection
    $f = Net::FTP->new($ftp_host) ;
    if ( $f eq "" ) {  
	$MSGERR[0] = "Could not open ftp connection to";
	$MSGERR[1] = "$ftp_host";
	&FATAL_ERROR();
    }
    else {	
	print PTR_LOGFILE "\t FTP->new : f = '$f' \n"; 
    }


    # login
    $STAT = ($f->login($ftp_user,$ftp_pw));
    if  ( $STAT eq "" )  {
	$MSGERR[0] = "Could not login as '$ftp_user;";
	&FATAL_ERROR();	
    }
    else {
	print PTR_LOGFILE "\t Logged in as '$ftp_user' \n";
    }

    # put each file to Suominet
    @FILELIST = qx(cd $DIR_SUOMINET ; ls ${SUOMINET_PREFIX}*${SUFFIX_GPSDATA} );
    $f->binary();
    foreach $file ( @FILELIST ) {
	$file =~ s/\s+$// ;   # trim trailing whitespace
	$STAT = $f->put("$DIR_SUOMINET/$file") ;
	if ( $STAT eq "" ) {
	    $MSGERR[0] = "Could not ftp-put file '$file'" ;
	    &FATAL_ERROR();  
	}
	else {
	    print PTR_LOGFILE "\t ->put $file  \n";
	}
    }

    # put each file to Suominet
    @FILELIST = qx(cd $DIR_SUOMINET ; ls ${SUOMINET_PREFIX}*${SUFFIX_WEATHER} );
    $f->ascii();
    foreach $file ( @FILELIST ) {
	$file =~ s/\s+$// ;   # trim trailing whitespace
	$STAT = $f->put("$DIR_SUOMINET/$file") ;
	if ( $STAT eq "" ) {
	    $MSGERR[0] = "Could not ftp-put file '$file'" ;
	    &FATAL_ERROR();  
	}
	else {
	    print PTR_LOGFILE "\t ->put $file  \n";
	}
    }

    # get list of Suominet files and make sure that each local 
    # file is actually at Suominet.

    @FILE_ls = $f->ls ;  # files at Suominet

    print PTR_LOGFILE "\n Verify each ftp'ed file: \n";
    $NERR = 0 ;

    foreach $file ( @FILELIST ) {
	$file =~ s/\s+$// ;   # trim trailing whitespace
	$FOUND = 0 ;
	print PTR_LOGFILE "\t Verify $file : ";

	foreach $file2 ( @FILE_ls ) {
	    $file2 =~ s/\s+$// ;   # trim trailing whitespace
	    if ( $file eq $file2 ) { $FOUND = 1; }
	}

	if ( $FOUND ) 
	{ print PTR_LOGFILE "SUCCESS\n" ; }
	else 
	{ print PTR_LOGFILE "FAILED\n" ;  $NERR++ ; }
    }

    if ( $NERR > 0 ) {
	$MSGERR[0] = "Failed to verify $NERR ftp files at Suominet.";
	&FATAL_ERROR();  
    }


}  # end of exec_ftp


### ----------------------------------
sub archive_FILES {

    # Call this function after ftp to  move ftp'ed files out of 
    # ftp dir and into archive dir for permanent local storage.

    my (@FILELIST, $file );

    print PTR_LOGFILE "\n" ;
    print PTR_LOGFILE " Archive ftp'ed files to \n\t $DIR_ARCHIVE \n" ;

    @FILELIST = qx(cd $DIR_SUOMINET ; ls ${SUOMINET_PREFIX}* );
    foreach $file ( @FILELIST ) {
	$file =~ s/\s+$// ;   # trim trailing whitespace
	print PTR_LOGFILE "\t archive $file \n";
	qx(cd $DIR_SUOMINET ; mv $file $DIR_ARCHIVE/ );
    }

} # end of archive_FILES 



### ---------------------------------------
sub clean_FILES {

    # remove temp files.

    my ($list, $ifile );

    if ( $CLEANFLAG == 0 ) { return ; }

    
    print PTR_LOGFILE "\n Remove temporary files in\n $DIR_CRONLOG\n";
    
    for ( $ifile=0; $ifile < $NFILE_GPSDATA ; $ifile++ ) {
	$list = $weatherFiles[$ifile] ;
	print PTR_LOGFILE "\t rm $list \n";
	qx($cdLOG ; rm $list 2>/dev/null ); 	
    }
    
    print PTR_LOGFILE "\t rm $weatherList \n";
    qx($cdLOG ; rm $weatherList ); 

}   # clean_FILES 

### -----------------------------------------
sub FATAL_ERROR {

    my ($msg, $ABORT_FILE);

    print PTR_LOGFILE "\n\n FATAL ERROR: \n";

    foreach $msg ( @MSGERR)  { print PTR_LOGFILE "    $msg \n"; }

    print PTR_LOGFILE "\n ***** ABORT ***** \n";
    close PTR_LOGFILE ;

    # leave ABORT file as stamp to be seen more clearly
    $ABORT_FILE = "$DIR_CRONLOG/ABORT_${CDATE_PROC}.STAMP" ;
    qx(touch $ABORT_FILE);

    exit(1);

}  # end of FATAL ERROR


