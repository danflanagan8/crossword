Crossword Module
================
This module makes it easy to add crossword puzzles that aremplayable in the
browser to your Drupal site. It is not for authoring puzzles; rather, it allows
you to upload crossword puzzle files that have been created elsewhere.

=Media Integration=
Install the submodule crossword_media to integrate with core media.

=How To Render a Playable Puzzle=
1. Install the crossword module.
2. Add a Crossword field to an entity (say a node type or media type
   called Puzzle).
3. On the Manage Display form for the Puzzle, select the "Crossword
   Puzzle" formatter for the Crossword field.
4. Create a new Puzzle using a crossword file in a supported format (Across
   Lite Text or Across Lite .puz).
5. View the Puzzle. You should see a fully functional puzzle.

==Keyboard Controls==
Arrow Keys: Move the active square around the grid
Tab: Next clue
Shift+Tab: Previous clue
Spacebar: Toggle direction
Enter/Return: Next square
Backspace/Delete: Delete input from active square and move to previous square

==Entering Text==
If you type a lowercase letter, the letter will be added to the active square
and it will be displayed as uppercase. Any text that was previously in the
active square is deleted. Further, the next square will become active.

If you enter an uppercase letter, the letter will be appended to the text in the
active square. This allows you to put multiple letters in the same square,
which is called a "rebus" puzzle. Hit Enter/Return to move to the next square.

=Accessibility: Puzzle For Screenreaders=
If you select the "Crossword Puzzle (screenreader)" formatter, the puzzle will
be displayed in a way that is optimized for use with a screenreader. Text is
input into a text field with each clue. Hitting return submits what the user
has typed to the grid. Like with the other formatters, uppercase letters are
grouped into a single (rebus) square. The grid is displayed, but it is not
interactive. There are no special keyboard controls.

=The Crossword Field Type=
This module provides a Crossword field type which is an extension of the
core File field type. The main difference is that files uploaded to a
crossword field must be able to be parsed by an installed Crossword File
Parser plugin. (This plugin type is defined and managed by this module.)
There are two crossword file types that can be parsed by Crossword File
Parser plugins included in this module: Across Lite Text (v1 and v2) and
Across Lite .puz files. Crossword File Parser plugins could be added
to support additional file types.

=Field Formatters=
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
formatters are used to render the file as a link (File Download Link and
Generic File) or an image (Crossword Thumbnail).


=Printing a Puzzle=
There is a library provided by this module that can be used to make the
puzzle look pretty good when it is printed. You may need to include additional
css in your own theme/library that hides site components that you don't want
printed with the puzzle, such as the header or footer, for example.

=Enhancing the Playable Puzzle=
When the puzzle is played in the browser, events in the browser cause methods
to be called on a Crossword object. The Crossword object then triggers events
on various DOM elements. There is a "crossword-solved" event that is triggered
when the puzzle is solved that you could easily use to start a victory
celebration.
