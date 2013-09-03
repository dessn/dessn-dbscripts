#!/bin/bash
#######CALLED BY runinbatchbyfield.sh IN ORDER TO PUT dessnpostprocessingbyfield.py INTO THE QUEUE ON FOLIO.SAS.UPENN.EDU


# request Bourne shell as shell for job
#$ -S /bin/bash

#Set the name of the job
#$ -N SN-pp 

#Use the local directory
#$ -cwd

# Merge the errors in with the stdout
#$ -j y

#Set the stdout file directory
#$ -o /data3/des/DESCANDANALYSIS/POSTPROCESSING/ppoutput

# Send mail when the job is finished or aborted
#$ -m ea

# Send mail to...
#$ -M johnfisc@sas.upenn.edu

echo $SGE_TASK_ID

REGISTERED=`grep "^$JOB_ID\$"  /data3/des/DESCANDANALYSIS/POSTPROCESSING/ppoutput/registry.txt`

if [ -z "$REGISTERED" ]
then
	echo $JOB_ID >> /data3/des/DESCANDANALYSIS/POSTPROCESSING/ppoutput/registry.txt
fi

source ~johnfisc/.bashrc


if [[ $SGE_TASK_ID -eq 1 ]]
    then
    field=X1
elif [[ $SGE_TASK_ID -eq 2 ]]
    then
    field=X2
elif [[ $SGE_TASK_ID -eq 3 ]]
    then
    field=X3
elif [[ $SGE_TASK_ID -eq 4 ]]
    then
    field=C1
elif [[ $SGE_TASK_ID -eq 5 ]]
    then
    field=C2
elif [[ $SGE_TASK_ID -eq 6 ]]
    then
    field=C3
elif [[ $SGE_TASK_ID -eq 7 ]]
    then
    field=E1
elif [[ $SGE_TASK_ID -eq 8 ]]
    then
    field=E2
elif [[ $SGE_TASK_ID -eq 9 ]]
    then
    field=S1
elif [[ $SGE_TASK_ID -eq 10 ]]
    then
    field=S2
fi



python dessnpostprocessingbyfield.py $field 
