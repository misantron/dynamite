IMAGE_NAME := amazon/dynamodb-local:2.6.1

.PHONY: initialize
initialize: start-docker

pipeline: test lint static-analyze

start-docker:
	docker pull $(IMAGE_NAME)
	docker start dynamite_dynamodb && exit 0 || \
	docker run -d -p 8000:8000 --name dynamite_dynamodb $(IMAGE_NAME) -jar DynamoDBLocal.jar
stop-docker:
	docker stop dynamite_dynamodb || true

test: start-docker test-unit test-integration

test-integration:
	vendor/bin/phpunit --testsuite Integration
test-unit:
	vendor/bin/phpunit --testsuite Unit

lint: rector-fix ecs-fix

ecs:
	vendor/bin/ecs check
ecs-fix:
	vendor/bin/ecs check --fix

rector:
	vendor/bin/rector process --clear-cache --dry-run
rector-fix:
	vendor/bin/rector process

static-analyze: phpstan psalm

phpstan:
	vendor/bin/phpstan analyse --memory-limit 1G
psalm:
	vendor/bin/psalm --threads=1
