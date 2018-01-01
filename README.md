# cpg-plugin-extensible-metadata

The Extensible Metadata plugin is designed for Coppermine Gallery 1.6.x.  Coppermine Gallery has built-in support for EXIF and IPTC metadata, but lacks support for XMP.  This plugin allows for the generation of XMP sidecar files containing additional metadata.  The metadata fields can then be selectively used for display with the images, or added to a search index.

## Installation

The plugin has the following dependencies:
- jQuery Update (http://forum.coppermine-gallery.net/index.php?topic=77595.0)
- jQuery UI (https://github.com/dpaulat/cpg-plugin-jquery-ui)

Recommended configuration:
- jQuery 3.2.1
- jQuery UI 1.12.1

Older versions of jQuery and jQuery UI may work, but are untested.

To prepare for install, after dependencies have been satisfied, place the contents of the repository into <cpg1.6.x root>/plugins/extensible_metadata/.  From there, the plugin may be installed through the standard plugin interface.

## Usage

Metadata fields will start populating when photos are uploaded.  When installing to a gallery with existing photos, perform an initial metadata refresh from the plugin configuration page.  This will iterate through the existing photos to gather the available fields.

After metadata fields have been populated, you can select what to do with each field.  For example, let's assume our photos have the "MPReg:PersonDisplayName" tag.  We can select this field for both display and indexing, and give it a more readable name (e.g., "People").  After adding a new field to the index, we must perform another metadata refresh.  Now, photos that have the "MPReg:PersonDisplayName" will display the "People" field under photo details.  Additionally, selected as an index, each person tagged in the photo will be searchable.  You can click a person's name, and it will search for other photos with that person.  Alternatively, you can perform the same action from the search page.

## Links

Coppermine Gallery:
- http://coppermine-gallery.net/
- https://github.com/coppermine-gallery/cpg1.6.x
