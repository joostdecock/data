#!/bin/bash
echo "Starting deploy script"
if [ -z "$TRAVIS_PULL_REQUEST" ]; then
    echo "Pull request, not deploying.";
    exit
else
    if [ "$TRAVIS_BRANCH" = "develop" ] || [ "$TRAVIS_BRANCH" = "master" ]; then
        if [ "$TRAVIS_PHP_VERSION" = "7.0" ]; then
            echo "Deploying PHP version $TRAVIS_PHP_VERSION build.";
            cd $TRAVIS_BUILD_DIR
            mkdir build
            mv src templates vendor public build/
            tar -czf freesewing.tgz build
            export SSHPASS=$FREESEWING_DATA_DEPLOY_PASS
            sshpass -e scp -o stricthostkeychecking=no freesewing.tgz $FREESEWING_DATA_DEPLOY_USER@$FREESEWING_DATA_DEPLOY_HOST:$FREESEWING_DATA_DEPLOY_PATH/$TRAVIS_BRANCH/builds
            sshpass -e ssh -o stricthostkeychecking=no $FREESEWING_DATA_DEPLOY_USER@$FREESEWING_DATA_DEPLOY_HOST "cd $FREESEWING_DATA_DEPLOY_PATH/$TRAVIS_BRANCH/builds ; tar -xzf freesewing.tgz ; rm freesewing.tgz ; rm -rf previous ; mv current previous ; mv build current ; cd current/public ; ln -s /fs/storage/data/static/ "
            echo "All done.";
        else
            echo "Build on PHP version $TRAVIS_PHP_VERSION, not deploying.";
        fi
    else
        echo "Branch is neither master nor develop, not deploying."
    fi
fi
echo "Bye"
