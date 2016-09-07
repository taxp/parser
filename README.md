**Usage**

Put csv files to 'files' directory, add permission to server to read them.
Add permission to server to white into site root directory - 
therefore it could write output csv.
Request index.php or open it in browser. But host must be set.

Generator.php was used to generate test files. It isn't needed for workaround.


**Performance**

50 files with total number of lines about 700K (14K per file)
were parsed approx. 135 seconds.