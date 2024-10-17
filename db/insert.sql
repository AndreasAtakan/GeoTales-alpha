/*******************************************************************************
* Copyright (C) Nordfjord EDB AS - All Rights Reserved                         *
*                                                                              *
* Unauthorized copying of this file, via any medium is strictly prohibited     *
* Proprietary and confidential                                                 *
* Written by Andreas Atakan <aca@geotales.io>, January 2022                  *
*******************************************************************************/

BEGIN;
INSERT INTO "User" (username, password, email, paid, stripe_id)
VALUES (
	'andreas',
	'240f2ec6918381f9e7393b18539f8a2c8bc60b3224004a37ee7057a62abc2efa',
	'andreascan.98@gmail.com',
	true,
	'cus_MDNgbrSN5IErCT'
);
END;
