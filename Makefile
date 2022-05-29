IMAGE_NAME := amazon/dynamodb-local:1.18.0

.PHONY: initialize
initialize: start-docker

start-docker:
	docker pull $(IMAGE_NAME)
	docker start dynamite_dynamodb && exit 0 || \
	docker run -d -p 8000:8000 --name dynamite_dynamodb $(IMAGE_NAME) -jar DynamoDBLocal.jar
stop-docker:
	docker stop dynamite_dynamodb || true
