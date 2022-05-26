docker-start:
	docker-compose up -d
docker-stop:
	docker-compose stop
docker-down:
	docker-compose down
test:
	vendor/bin/simple-phpunit
