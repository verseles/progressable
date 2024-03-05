
test:
	composer test

ai:
	./merge.bash --folder-recursive="." \
	--ignore-folders=vendor \
	--ignore-extensions=lock,bash \
	--ignore-files=LICENSE.md \
	--ignore-files=makefile
