# Wsdl2Magento #

----------

**Version 0.1.0**

**Date 09/09/2012**

----------

# Français #

## Description ##

Ce module Magento permet de générer à partir de fichier WSDL les classes correspondantes aux "ComplexType" du Webservice SOAP. Les classes Services sont également créées afin de facilement manipuler les appels aux Webservices.

## Installation ##
Copier les fichiers à la racine du projet Magento.
Modifier le fichier app/code/community/Level42/Wsdl2Magento/etc/wsdl2magento.xml pour configurer les Webservices avec lesquels communiquer.

Il est possible de créer plusieurs blocs "configuration", avec comme balise "env" le nom de l'environnement (dev, int, prod etc...).

<configuration env="dev">

Chaque noeud "webservice" porte un nom unique et contient la configuration des classes à générer.


----------


# English #

## Description ##
This module for Magento allows to generate "ComplexType" classes from WSDL's SOAP Web Service. Those generated service classes can be easily used to call web services methods.

## Installation ##
Copy files on the Magento project root. Edit app/code/community/Level42/Wsdl2Magento/etc/wsdl2magento.xml file to configure Webservices.
It's possible to create several "configuration" blocks, with "env" tag which represent the environment name (dev, int, prod etc...).

<configuration env="dev">

Each "webservice" node will have a unique name and contains configuration for classes generation.