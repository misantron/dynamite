IMAGE_NAME := amazon/dynamodb-local:1.18.0

.PHONY: initialize
initialize: start-docker

start-docker:
	docker pull $(IMAGE_NAME)
	docker start dynamite_dynamodb && exit 0 || \
	docker run -d -p 8000:8000 --name dynamite_dynamodb $(IMAGE_NAME) -jar DynamoDBLocal.jar
stop-docker:
	docker stop dynamite_dynamodb || true

test: start-docker test-unit test-integration

test-integration:
	vendor/bin/simple-phpunit --testsuite Integration
test-unit:
	vendor/bin/simple-phpunit --testsuite Unit

lint: ecs-fix phpstan

ecs:
	vendor/bin/ecs check
ecs-fix:
	vendor/bin/ecs check --fix
phpstan:
	vendor/bin/phpstan analyse

