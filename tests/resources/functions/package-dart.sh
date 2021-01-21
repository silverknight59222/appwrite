
echo 'Dart Packaging...'

cp -r $(pwd)/tests/resources/functions/dart $(pwd)/tests/resources/functions/packages/dart

docker run --rm -v $(pwd)/tests/resources/functions/packages/dart:/app -w /app appwrite/env-dart-2.10.4:1.0.0 dart pub get

docker run --rm -v $(pwd)/tests/resources/functions/packages/dart:/app -w /app appwrite/env-dart-2.10.4:1.0.0 tar -zcvf code.tar.gz .

mv $(pwd)/tests/resources/functions/packages/dart/code.tar.gz $(pwd)/tests/resources/functions/dart.tar.gz

rm -r $(pwd)/tests/resources/functions/packages/dart
