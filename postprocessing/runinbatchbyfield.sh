#!/bin/bash
#THIS SCRIPT IS JUST USED TO IMPLEMENT POST PROCESSING ON FOLIO.SAS.UPENN.EDU.
#v1.0


pr=$1
  qsub -V -l h_vmem=4g -l h_stack=256m -l $pr -t 1-10:1 batchppbyfield.sh  
