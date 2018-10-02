# xklid101/h40/bin-assets

H40 cli scripts and other assets

**************************
**************************

### /src/generate.mdfile.php
**************************

Generates markdown file from all files (extension doesn't matter) contents in specified directory (or file).  
Simply by getting text from php-like docblock-comments annotaded with @md and inserting it into .md file.  
See this file comment annotations to understand how it works  
'composer.json' file in defined directory is used to generate heading and basic description.  
Markdown file will be saved into parsed directory or next to parsed file.;  
  
for help and how to use run from terminal:  
```bash  
~$ php generate.mdfile --help  
```
  
**************************
**************************

## License  
MIT  

## Authors  
{
    "name": "xklid101"
}  
