# Laravel Version Mask Map in Taiwan
## Require
* php
* composer
* docker
## Install Relation Library
```shell
> composer install
```
## Usage
### Run the site
1. Launch your docker
   ```shell
   > open -a docker
   ```
2. Launch lavadock 
   ```shell
   > cd lavadock
   > docker-compose up -d nginx mysql redis workspace
   ```
3. Use browser to show
![](https://i.imgur.com/t5K1gZW.png)


### Search
When type the url of the website, we can use 'i' and 'd' to filter the date that have specific keyword in institution or address.

### Example
![](https://i.imgur.com/Xi7cAgj.png)

### Down the server
```shell
 docker-compose down
```