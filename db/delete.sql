/*******************************************************************************
* Copyright (C) Nordfjord EDB AS - All Rights Reserved                         *
*                                                                              *
* Unauthorized copying of this file, via any medium is strictly prohibited     *
* Proprietary and confidential                                                 *
* Written by Andreas Atakan <aca@geotales.io>, January 2022                  *
*******************************************************************************/

BEGIN;

DELETE FROM "Analytics";
DELETE FROM "User_Upload";
DELETE FROM "Upload";
DELETE FROM "Comment";
DELETE FROM "Reaction";
DELETE FROM "Flag";
DELETE FROM "View";
DELETE FROM "User_Map";
DELETE FROM "Map";
DELETE FROM "User";

END;
