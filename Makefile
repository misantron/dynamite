start-docker:
	docker pull localstack/localstack:0.14.3
	docker start dynamite_localstack && exit 0 || \
	docker run --name dynamite_localstack localstack/localstack:0.14.3 -d -p 4566:4566 -p 4575:4566 -e SERVICES=dynamodb -v /var/run/docker.sock:/var/run/docker.sock && \
    docker run --rm --link dynamite_localstack:localstack martin/wait -c localstack:4566
stop-docker:
	docker stop dynamite_localstack || true
