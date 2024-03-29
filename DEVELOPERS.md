# Developers
This is a guide for developers who wish to develop the plugin on their local machine.

## Environment requirements

1. Node 18+
2. PHP 7.2+
3. Docker compose

## Commands

### WordPress enviroment

To setup the WordPress we use docker compose. It basically does two things:

1. Run a setup script if this is the first time. This will setup the whole environment for the docker containers to use.
2. Run the necessary docker containers in docker compose

You can use the folowing command for this:

```shell
npm run up
```

To stop the docker compose:

```shell
npm run stop
```

To fully remove the docker images and volumes use:

```shell
npm run down
```

### Build assets

To build the JS and CSS assets you can use the following command:

```shell
npm run build:assets
```

### Generate translations

To generate the translations into .mo files you can use the following command:

```shell
npm run build:i18n
```

### Create release files

To create the release files you can run:

```shell
npm run build:release
```

This will create a release folder with the release files and a zip file of that folder.

You can also run:

```shell
npm run build
```

This will run all build tasks before running the build:release task.
