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
      console.log(data);
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
      this.$clue;
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
      addCluesToSquares();

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

      this.setActiveSquare = function(row, col) {
        var grid = Crossword.data.puzzle.grid;
        if (row >= 0 && row < grid.length && col >= 0 && col < grid[row].length && grid[row][col].fill !== null) {
          this.activeSquare = {'row' : row, 'col': col};
          if (this.dir == 'across') {
            try {
              this.activeClue = this.data.puzzle.grid[row][col].across.index;
            }
            catch(error) {
              this.activeClue = null;
            }
          }
          else {
            try {
              this.activeClue = this.data.puzzle.grid[row][col].down.index;
            }
            catch(error) {
              this.activeClue = null;
            }
          }
          this.setActiveReferences();
        }
        return this;
      }

      this.setActiveClue = function(index) {
        if (this.dir == 'across') {
          if (index >= 0 && index < this.data.puzzle.clues.across.length) {
            this.activeClue = index;
            this.setActiveReferences();
            var numeral = this.data.puzzle.clues.across[index].numeral;
            var grid = this.data.puzzle.grid;
            for (var row_index = 0; row_index < grid.length; row_index++) {
              for (var col_index = 0; col_index < grid[row_index].length; col_index++) {
                if (grid[row_index][col_index].numeral == numeral) {
                  this.activeSquare = {
                    'row' : row_index,
                    'col' : col_index,
                  };
                  return this;
                }
              }
            }
          }
        }
        else {
          if (index >= 0 && index < this.data.puzzle.clues.down.length) {
            this.activeClue = index;
            this.setActiveReferences();
            var numeral = this.data.puzzle.clues.down[index].numeral;
            var grid = this.data.puzzle.grid;
            for (var row_index = 0; row_index < grid.length; row_index++) {
              for (var col_index = 0; col_index < grid[row_index].length; col_index++) {
                if (grid[row_index][col_index].numeral == numeral) {
                  this.activeSquare = {
                    'row' : row_index,
                    'col' : col_index,
                  };
                  return this;
                }
              }
            }
          }
        }
      }

      this.changeDir = function() {
        this.dir = this.dir == 'across' ? 'down' : 'across';
        this.setActiveSquare(this.activeSquare.row, this.activeSquare.col);
      }

      this.moveActiveSquare = function(rows, cols) {
        this.setActiveSquare(this.activeSquare.row + rows, this.activeSquare.col + cols);
        return this;
      }

      this.advanceActiveSquare = function() {
        if (this.dir == 'across') {
          this.moveActiveSquare(0, 1);
        }
        else {
          this.moveActiveSquare(1, 0);
        }
        return this;
      }

      this.retreatActiveSquare = function() {
        if (this.dir == 'across') {
          this.moveActiveSquare(0, -1);
        }
        else {
          this.moveActiveSquare(-1, 0);
        }
        return this;
      }

      this.advanceActiveClue = function() {
        this.setActiveClue(this.activeClue + 1);
        return this;
      }

      this.retreatActiveClue = function() {
        this.setActiveClue(this.activeClue - 1);
        return this;
      }

      this.setActiveReferences = function() {
        if (this.dir == 'across') {
          this.activeReferences = this.data.puzzle.clues.across[this.activeClue].references;
        }
        else {
          this.activeReferences = this.data.puzzle.clues.down[this.activeClue].references;
        }
      }

      this.setAnswer = function(letter) {
        this.answers[this.activeSquare.row][this.activeSquare.col] = letter;
      }

      this.setActiveClue(0);

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

      function addCluesToSquares() {
        var grid = Crossword.grid;
        var clues = Crossword.clues;
        var dirs = {'across' : true, 'down' : true};

        for (var row_index = 0; row_index < grid.length; row_index++) {
          for (var col_index = 0; col_index < grid[row_index].length; col_index++) {
            var Square = grid[row_index][col_index];
            for (var dir in dirs) {
              if (Square[dir] > -1) {
                Square[dir] = clues[dir][Square[dir]];
              }
            }
          }
        }
      }
    }
  }

})(jQuery, Drupal, drupalSettings);
