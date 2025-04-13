# Payment gateway integration task
### How it works
##### clone project:
git clone https://github.com/Mahmoud-ElBoshy/integration-dockerized.git

##### install packages:
composer install
##### Enable the Docker support of Symfony Flex:
 composer config --json extra.symfony.docker 'true'
##### Build the Docker images:
docker compose build --no-cache --pull
##### Start the project:
 docker compose up -d

##### project will run on localhost
Note: You have collection file called [Integration.postman_collection.json](https://github.com/Mahmoud-ElBoshy/integration-dockerized/blob/main/Integration.postman_collection.json "Integration.postman_collection.json") that contain end point and documentation for it.
