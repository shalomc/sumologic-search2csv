# Sumologic-search2csv

A simple PHP script to retrieve Sumologic query results, format them as CSV and send via email. 

## About
I've been asked why php. 
It is php because our customer support and billing is WHMCS, which is written in php. 
This repository is a side effect of development done on that platform.


## Usage
The script reads the contents of the search file, which should include a valid Sumologic query. 
It then queries Sumologic using the credentials in the config file, and saves the results as json or csv. 

php sumologic-search2csv <path-to-search-file> <path-to-config-file> csv|json

All parameters are optional. If no parameters are passed then the script uses default values. 

## License
Copyright 2015 Shalom Carmel

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

	http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.