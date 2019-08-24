Crossword Module
================
This module makes it easy to add crossword puzzles that aremplayable in the
browser to your Drupal site. It is not for authoring puzzles; rather, it allows
you to upload crossword puzzle files that have been created elsewhere.

The Crossword Field Type
========================
This module provides a Crossword field type which is an extension of the
core File field type. The main difference is that files uploaded to a
crossword field must be able to be parsed by an installed Crossword File
Parser plugin. (This plugin type is defined and managed by this module.)
There are two crossword file types that can be parsed by Crossword File
Parser plugins included in this module: Across Lite Text (v1 and v2) and
Across Lite .puz files. Crossword File Parser plugins could be added
to support additional file types.

Field Formatters
================
There is a suite of field formatter plugins for displaying a Crossword
field. All of the field formatters produce markup that is highly
themeable. Nearly all components of the puzzle can be configured or
templated. The crossword can be formatted as a self contained unit
[Crossword Puzzle, Crossword Puzzle (book style), Crossword Solution].
The Crossword field can also be rendered using something like pseudofields
[Crossword Puzzle (pseudofields)] such that the different components of the
puzzle can easily intermingle with other fields on the node (or other entity)
to which the Crossword field is attached. Of the formatters mentioned so far,
all except for the Crossword Solution formatter handle provide all of the js
necessary to make the puzzle fully playable in the browser. The remaining
formatters (File Download Link and Generic File) are used to render the file as
a link.

Printing a Puzzle
=================
There is a library provided by this module that can be used to make the
puzzle look pretty good when it is printed. You may need to include additional
css in your own theme/library that hides site components that you don't want
printed with the puzzle, such as the header or footer, for example.

Enhancing the Playable Puzzle
=============================
When the puzzle is played in the browser, events in the browser cause methods
to be called on a Crossword object. The Crossword object then triggers events
on various DOM elements. There is a "crossword-solved" event that is triggered
when the puzzle is solved that you could easily use to start a victory
celebration.
