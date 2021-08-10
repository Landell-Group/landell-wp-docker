# Getting started

1. Fork or duplicate repository
``

# example of how to duplicate:
git clone --bare git@github.com:Landell-Group/landell-wp-docker.git
cd landell-wp-docker.git
git push --mirror git@github.com:emanuellandell/wp-sverigeshopping.git
cd ..
rm -rf landell-wp-docker.git
``
More details: https://docs.github.com/en/github/creating-cloning-and-archiving-repositories/creating-a-repository-on-github/duplicating-a-repository

2. Put database in schema/
3. Put wordpress files e.g. wp-content into wordpress/
4. docker-compose up

Browse to http://localhost:8080
