#!/usr/bin/env bash
# !!! NEVER UPLOAD !!!
# local script for renaming the 7 folders that get pushed
# to execute run "./_push.sh" in the root JVZoo folder

echo "Prepping folders..."
echo ""

declare -a Files=("mhapi/resources" "mhapi/public" "mhapi/app" "mhapi/config")
#Files[0] = controllers
#Files[1] = helpers

if [ -d "mhapi/resources.new" ]; then
    echo "  Removing folders back to development..."
    for i in "${Files[@]}"
    do
        rm -rf $i.new
        echo "   - removing $i.new"
    done
    echo ""
    echo ""
    echo "Ready to develop"
    echo ""
else
    echo "  Copying folders for deployment (.new)..."

    for i in "${Files[@]}"
    do
        cp -r $i $i.new
        echo "   - renaming $i to $i.new"
    done
    echo ""
    echo ""
    echo "READY TO PUSH!!!"
    echo ""
fi
