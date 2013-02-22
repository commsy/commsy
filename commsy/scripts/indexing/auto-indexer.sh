#!/bin/sh

# call it via shell for testing...
# e.g.: auto-indexer /usr/bin/swish-e /opt/lampp/htdocs/commsy var/101/103/ >> /opt/lampp/htdocs/commsy/var/101/103/ft_idx.log &
# or via PHP
# e.g.: ;-)

# $1 -> search engine		(e.g. /usr/bin/swish-e)
# $2 -> search site base	(e.g. /opt/lampp/htdocs/commsy)
# $3 -> index base		(e.g. var/101/103/)

echo
echo "################################################"
echo "### Starting swish-e Auto-Indexer for CommSy ###"
echo "################################################"
echo `date`
echo

cd $2

if test -w ${3}ft.index
then

  echo
  echo "!!! Info: index found... updating..."
  echo
  # backup last index
  cp ${3}ft.index ${3}ft.index_last
  cp ${3}ft.index.prop ${3}ft.index.prop_last

  $1 -e -N ${3}ft.index -c etc/ft_config/ft.config -i ${3} -f ${3}ft.temp

  if test -w ${3}ft.temp.temp
  then

    # NO new files available
    echo
    echo "Info: no new files available ... restoring old index!"
    echo
    rm ${3}ft.temp.temp
    rm ${3}ft.temp.prop.temp
    mv ${3}ft.index_last ${3}ft.index
    mv ${3}ft.index.prop_last ${3}ft.index.prop

  else

    # new files available
    echo
    echo "Info: new files available ... updating current index!"
    echo
    rm ${3}ft.index_last
    rm ${3}ft.index.prop_last

    cd $3
    $1 -e -M ft.index ft.temp ft.merge
    rm ft.temp
    rm ft.temp.prop
    rm ft.index
    rm ft.index.prop
    mv ft.merge ft.index
    mv ft.merge.prop ft.index.prop

  fi

else

  echo
  echo "Info: no index found ... creating new one!"
  echo
  $1 -e -c etc/ft_config/ft.config -i ${3} -f ${3}ft.index

fi
