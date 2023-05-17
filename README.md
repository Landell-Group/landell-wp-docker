# Getting started



## Part 1 - Fork or duplicate repository
``
# example of how to duplicate:
git clone --bare git@github.com:Landell-Group/landell-wp-docker.git
cd landell-wp-docker.git
git push --mirror git@github.com:emanuellandell/wp-sverigeshopping.git
cd ..
rm -rf landell-wp-docker.git
``
More details: https://docs.github.com/en/github/creating-cloning-and-archiving-repositories/creating-a-repository-on-github/duplicating-a-repository

## Part2 - Fetch files from existing wp server
1. Archive & download wp-content/* from server into wp-content.zip
2. Export database using e.g. php-my-admin
3. Put database in files/schema/ (1 or more .sql)
4. Put wordpress files e.g. wp-content into files/wp-content/

5. Make sure that wp-includes/version.php contains the same wp_version as the live site. You might need to update the files in wordpress/ folder; by extracting from zip. (https://wordpress.org/download/releases/). If you do; delete wordpress/wp-content

6. Update the following property in docker-compose.yml to match with your db file: 
WORDPRESS_TABLE_PREFIX: KsjdvaWM_

6. docker-compose up

Browse to http://localhost:8080

If you have problems with routing; you prob. have to reset permalinks:
- http://localhost:8080/wp-admin/options-permalink.php
