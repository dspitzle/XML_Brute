# XML_Brute

<h4>A PHP application for converting arbitrary XML files into relational databases</h4>

Table of contents:
* [Introduction](#introduction)
* [Setup](#setup)
* [Using the Application](#using-the-application)
* [Moving Forward](#moving-forward)

NOTE: The primary version of this file is located in wwwroot\XML_Brute

## Introduction

XML_Brute is a PHP application that will accept an arbitrary XML file and generate a relational database as output.  At this stage it has undergone limited testing, and practically speaking it is limited to use in Windows environments, though further development should make it usable and useful on other OSes.

XML_Brute was originally developed for use at the [Washtenaw Intermediate School District](http://washtenawisd.org) as a tool for analyzing data in our county's state reporting files, which include several file specifications that have slowly changed over time.  XML_Brute was built to live up to its name, simply bulldozing its way through an XML file to develop a data structure map, instantiate that map as a relational database in one of several formats, and then run through the file a second time to insert the data into the database.  Currently the application provides a log of the process as output to the webbrowser.

XML_Brute has been verified to run on Windows 7 serving 32-bit PHP version 5.6.30.  It makes use of [Twitter Bootstrap v3.1](https://getbootstrap.com) for layout purposes.  Long-term the goal is to ensure XML_Brute can run on Linux and Macs, but that's currently beyond my capabilities.  Bug reports will be happily accepted.

## Setup

1. Directory Placement

   XML_Brute is split into two separate directories.  `wwwroot\XML_Brute` contains the user interface files, and its contents should be placed in a folder in your webroot, e.g. if your webroot is `C:\user\public_html`, then the __contents__ of `wwwroot\XML_Brute` should be placed in `C:\user\public_html\XML_Brute`.  The `XML_Brute_base` folder contains PHP scripts and temporary storage for uploaded and downloadable files.  In order to ensure that users cannot directly access any uploads (and thus open you to remote code execution), it should be placed outside of your webroot, e.g. in the example above, a good location would be `C:\user\XML_Brute_base`.  

1. Directory Permissions

   Whichever user account executes the PHP code (IUSR in most Windows setups using IIS) must have upgraded permisisons to certain directories

   * `XML_Brute_base\storage\uploads` must have __read__, __write__, and __modify__ access enabled so that files can be moved into it and deleted when no longer required.
   * `XML_Brute_base\storage\downloads` must have __read/execute__, __write__, and __modify__ permissions so that files can be moved into and out of it, accessed via PHP's PDO library, and deleted when no longer required.
   * `wwwroot\XML_Brute` must have __read__ and __write__ permissions so that files can be moved into it, and downloaded by the end user

1. Settings in `php.ini`

   Working with larger XML files will probably require bumping up several environment values in `php.ini`, specifically 
   * `max_execution_time` (suggested value 300, which is 5 minutes)
   * `memory_limit` (suggested value 512M)
   * `upload_max_filesize` (depends on files you're working with, suggested value 10M)
   * `post_max_size` (larger than upload_max_filesize, suggested value 11M)
 In addition, after enabling your preferred output types (see `config.ini.php` below), be sure to activate the corresponding PDO libraries in `php.ini` where appropriate.

1. Rename `config-sample.ini.php` to `config.ini.php`
   In order to avoid overwriting users' local configurations, XML_Brute ships with a configuration file named `config-sample.ini.php` but actually works with `config.ini.php`.
 
1. Settings in `config.ini.php`

   The `config.ini.php` file contains the following things which should be customized to meet your setup

   * A list of output formats (some which may still awaiting development), using standard `.ini` file syntax:  add a semicolon in front of one of the options to disable it, remove the semicolon to re-enable it.
   * `DbFormatsDefault` should be set to whichever of the enabled formats you would like to have as the default pre-selected option when running the application.
   * `BaseDir` should identify where the `XML_Brute_base` directory was placed


### MS Access ODBC Drivers
XML_Brute is capable of producing MS Access 2010 `.accdb` database files as output.  This requires that the appropriate ODBC 
drivers be installed on the computer that will be running the script.  While most modern Windows machines are running a 64-bit OS, many
PHP installations are still 32-bit because the 64-bit versions are experimental.  This creates a conflict because the 32-bit version of 
PHP wants to use 32-bit ODBC drivers which are usually absent on 64-bit Windows, but they can be installed; guidance on doing so can be
found at http://www.weberpackaging.com/pdfs/How%20to%20get%2032_bit%20ODBC%20Drivers%20on%20Win7_64bit%20PC.pdf

## Using the Application
The opening screen is simply a form containing a file download field and a dropdown for selecting the type of output file.  When the form is 
submitted, the application runs in three steps:

1.  The entire XML file is scanned to construct a map of the implicit data structure; the map is stored as an associative array.
1.  A relational database of the user's chosen format is instantiated, and the tables called for by the map are created within it.
1.  The XML file is scanned a second time, storing the data it contains in the appropriate tables.

The application dumps status and progress information to the browser as it goes, allowing debugging of the output, and providing the database 
map so the relationships between the tables are apparent.

### Standalone Use
For those who don't have PHP servers lying around, I recommend downloading [PHP Desktop](../../../../cztomczak/phpdesktop).  It 
sets up a single-use webserver for executing a particular PHP application.  Check the README file at the repository for links 
to download the compiled program.  After you've downloaded PHP Desktop, test it to ensure it's running, and then copy the contents of `wwwroot\XML_Brute` 
into the `www` subdirectory (this will overwrite PHP Desktop's default `index.php` file, so you'll get a popup verifying you want to 
do that).  Follow the other instructions above under [Setup](#setup), and then you can run PHP Desktop to get an instance of XML_Brute.

## Moving Forward
The near-term development goals for XML_Brute are to add more output formats, upgrade the code commenting to PHPDoc standards, and configure the output based on user preferences - full log dump to the browser, status summaries, or output to log file.  I am extremely interested in bug reports, as well as suggestions for cross-compatibility with other OSes, as well as other software and OS versions.  If you're interested in getting involved, check out the [Contributor guidelines](CONTRIBUTING.md).
