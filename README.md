# PubState Plugin

- Author: Ronald Steffen
- Version: 1.0.0.0

## Description

An OMP plugin to provide a field "Publication State" on the submission workflow tab "Publication" under "Title & Abstracts". This field is used by other plugins (as bookPage, seriesIndexPage catalogSearchPage) to label a publications state on the frontend.

## Usage

To style the "Forthcoming/Superseded" labels on the bookPage and CatalogSearch templates use the journal stylesheet and provide a "pubState" class:

    .pubSate {
        color: red;
    }

## License

Copyright (c) 2021 Language Science Press

Distributed under the GNU GPL v3. For full terms see the file docs/LICENSE.

## System Requirements

This plugin is compatible with...

- OMP 3.2.1
