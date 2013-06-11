#!/usr/bin/env python
import os
from distutils.core import setup

scripts = ['get-des-lightcurves']
scripts = [os.path.join('scripts', s) for s in scripts]

setup(name="dessn", 
      version="0.1dev",
      description="DES Supernova-related scripts and utilities",
      author="DES supernova working group",
      author_email="kylebarbary@gmail.com",
      packages=['dessn'],
      scripts=scripts)
