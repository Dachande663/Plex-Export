# PLEX Export

Luke Lanchester <luke@lukelanchester.com>
http://hybridlogic.co.uk/code/standalone/plex-export/


> Please note: Plex Export is no longer under active development. Use at your own risk.


## Summary

Plex Export allows you to produce an HTML page with information on the media contained within your Plex library. This page can then be shared publicly without requiring access to the original Plex server.


## Features

- Provides an overview of all media in each of your library sections
- Images are lazy loaded as you scroll down
- Live filtering within each section
- View additional item information on click


## Instructions

### Install Pre-Requisites
Make sure you have PHP installed and SimpleXML is enabled in your system. In Ubuntu 18.04, the following should suffice:

```
sudo apt install php libapache2-mod-php
sudo systemctl restart apache2
sudo apt install php-xml
```
PLEX Export does not have to be run on the system containing Plex Media Server however. In other words, if you are running Plex on a server, such as a Synology NAS, you may run it on a local machine with access to the network with the local host (one with the port 32400). 



### Running 
1. In your preferred shell/terminal enter the following command: 
```
php cli.php
```
1. If Plex Media Server is running on a different machine, specify it's URL with the `-plex-url` parameter e.g. 
```
php cli.php -plex-url=http://other-machine.local:32400
```
1. Upon completion your plex-data directory will now contain a .js file and any related thumbnails. Access the index.html file in your web browser and enjoy :) Note that you must launch an HTML server, such as 
```
python -m html.server
```
And then run the `index.html` file


## Notes

* The website is designed for modern browsers (Safari, Chrome, Firefox)
* You may need to chmod cli.php to allow for executables
* Delete cli.php if you upload PLEX Export to any public location
* If your Plex Server is running in Home mode, we need to authenticate via a token
* To get a valid token for your system, look here: https://support.plex.tv/hc/en-us/articles/204059436-Finding-your-account-token-X-Plex-Token
* Then when running cli.php, add a parameter like: -token=<Your Token>


## Features

* Filter by genre, actors etc
* Sort by name, year, rating etc
* Pull in favourites from Plex
* More detailed TV Show popup (seasons, episodes etc)
