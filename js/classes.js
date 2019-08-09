(function ($, Drupal, drupalSettings) {

  Drupal.Crossword = {
    Square: function(data) {
      /**
       * When created, it just has row, col, fill, numeral.
       * Later, it can get connected to Clues.
       */
      this.row = data.row;
      this.column = data.col;
      this.fill = data.fill ? data.fill.toUpperCase() : null;
      this.numeral = data.numeral;
      this.across = data.across ? data.across.index : null;
      this.down = data.down ? data.down.index : null;
      this.moves = {
        'up' : false,
        'down' : false,
        'left' : false,
        'right' : false,
      };
      this.$square = null;
    },

    Clue: function(data) {
      this.text = data.text;
      this.dir = data.dir;
      this.index = data.index;
      this.numeral = data.numeral;
      this.references = data.references; //starts as contstants. objects get added later
      this.squares = [];
      this.$clue = null;
    },

    Crossword: function(data) {
      var Crossword = this;
      this.data = data;

      this.dir = 'across';
      this.activeSquare = {'row' : null, 'col': null};
      this.activeClue = null;
      this.activeReferences = [];
      this.answers = emptyAnswers();
      this.grid = makeGrid();
      this.clues = makeClues();
      connectCluesAndSquares();

      function emptyAnswers() {
        var grid = Crossword.data.puzzle.grid;
        var answers = [];
        for (var row_index = 0; row_index < grid.length; row_index++) {
          answers.push([]);
          for (var col_index = 0; col_index < grid[row_index].length; col_index++) {
            answers[row_index].push(null);
          }
        }
        return answers;
      }

      this.setActiveSquare = function(Square) {
        this.activeSquare = Square;
        this.activeClue = Square[this.dir];
        return this;
      }

      this.setActiveClue = function(Clue) {
        this.activeClue = Clue;
        this.dir = Clue.dir;
        this.activeSquare = Clue.squares[0];
        this.activeReferences = Clue.references;
        return this;
      }

      this.changeDir = function() {
        this.dir = this.dir == 'across' ? 'down' : 'across';
        this.setActiveSquare(this.activeSquare);
        return this;
      }

      this.moveActiveSquare = function(move) {
        if (this.activeSquare.moves[move]) {
          this.setActiveSquare(this.activeSquare.moves[move]);
        }
        return this;
      }

      this.advanceActiveSquare = function() {
        if (this.dir == 'across' && this.activeSquare.moves['right']) {
          this.moveActiveSquare('right');
        }
        else {
          this.moveActiveSquare('down');
        }
        return this;
      }

      this.retreatActiveSquare = function() {
        if (this.dir == 'across' && this.activeSquare.moves['left']) {
          this.moveActiveSquare('left');
        }
        else {
          this.moveActiveSquare('up');
        }
        return this;
      }

      this.advanceActiveClue = function() {
        if (this.clues[this.dir][this.activeClue.index + 1]) {
          this.setActiveClue(this.clues[this.dir][this.activeClue.index + 1]);
        }
        return this;
      }

      this.retreatActiveClue = function() {
        if (this.clues[this.dir][this.activeClue.index - 1]) {
          this.setActiveClue(this.clues[this.dir][this.activeClue.index - 1]);
        }
        return this;
      }

      this.setAnswer = function(fill) {
        this.answers[this.activeSquare.row][this.activeSquare.col] = fill;
      }

      function makeGrid() {
        var grid = [];
        var data_grid = Crossword.data.puzzle.grid;

        // start by creating objects
        for (var row_index = 0; row_index < data_grid.length; row_index++) {
          var row = [];
          for (var col_index = 0; col_index < data_grid[row_index].length; col_index++) {
            row[col_index] = new Drupal.Crossword.Square(data_grid[row_index][col_index]);
          }
          grid.push(row);
        }
        // now connect the moves
        for (var row_index = 0; row_index < data_grid.length; row_index++) {
          for (var col_index = 0; col_index < data_grid[row_index].length; col_index++) {
            var square = grid[row_index][col_index];
            for (move in data_grid[row_index][col_index]['moves']) {
              if (data_grid[row_index][col_index]['moves'][move]) {
                square.moves[move] = grid[data_grid[row_index][col_index]['moves'][move].row][data_grid[row_index][col_index]['moves'][move].col];
              }
            }
          }
        }
        return grid;
      }

      function makeClues() {
        var clues = {
          'across' : [],
          'down' : [],
        };
        var dirs = {'across' : true, 'down' : true};
        for (var dir in dirs) {
          var data_clues = Crossword.data.puzzle.clues[dir];
          for (var i = 0; i < data_clues.length; i++) {
            data_clues[i].index = i;
            data_clues[i].dir = dir;
            clues[dir].push(new Drupal.Crossword.Clue(data_clues[i]));
          }
        }

        // connect references
        for (var dir in dirs) {
          for (var i = 0; i < clues[dir].length; i++) {
            if (clues[dir][i].references) {
              var refs = clues[dir][i].references
              for (var ref_index in refs) {
                refs[ref_index]['clue'] = clues[refs[ref_index].dir][refs[ref_index].index];
              }
            }
          }
        }
        return clues;
      }

      function connectCluesAndSquares() {
        var grid = Crossword.grid;
        var clues = Crossword.clues;
        var dirs = {'across' : true, 'down' : true};

        for (var row_index = 0; row_index < grid.length; row_index++) {
          for (var col_index = 0; col_index < grid[row_index].length; col_index++) {
            var Square = grid[row_index][col_index];
            for (var dir in dirs) {
              if (Square[dir] !== null) {
                clues[dir][Square[dir]]['squares'].push(Square);
                Square[dir] = clues[dir][Square[dir]];
              }
            }
          }
        }
      }

      this.setActiveClue(this.clues.across[0]);

    }
  }

})(jQuery, Drupal, drupalSettings);
