<?php

/* 
 * Created by Hei
 */

/*
 * namespace file for all the Exceptions
 */

SimPHPfy::definePackage(array(
    'SimPHPfyBaseException', 
    'HttpException', 
    'FileNotFoundException', 
    'SimPHPfyException', 
    'ClassCollisionException', 
    'MissingClassFileException', 
    'UnknownClassException',  
    'InvalidPackagePathException', 
    'ArgumentTypeMismatchException', 
    'InvalidRouteException', 
    'InvalidControllerException', 
    'MissingControllerException', 
    'InvalidTemplateException', 
    'MissingTemplateException', 
    'InvalidViewException', 
    'InvalidDataSourceException', 
    'DataSourceConnectionException', 
    'DatabaseConnectionException', 
    'InvalidModelException', 
    'MissingModelException', 
    'DatabaseORMException', 
    'DatabaseDataRowException', 
    'InvalidModelDataRowException'
), EXCEPTION);
