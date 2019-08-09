(function ($, Drupal, drupalSettings) {

  Drupal.Crossword = {
    Square: function(data) {
      /**
       * When created, it just has row, col, fill, numeral.
       * Later, it can get connected to Clues.
       */
      this.row = data.row;
      this.column = data.col;
      this.fill = null;
      if (data.fill !== null) {
        if (data.fill.length > 1) {
          this.fill = data.fill.toUpperCase(); //uppercase means rebus
        }
        else {
          this.fill = data.fill.toLowerCase();
        }
      }
      this.answer = null;
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
        if (Square.fill !== null) {
          this.sendOffEvents();
          this.activeSquare = Square;
          this.activeClue = Square[this.dir];
          this.activeReferences = Square[this.dir].references;
          this.sendOnEvents();
        }
        return this;
      }

      this.setActiveClue = function(Clue) {
        this.sendOffEvents();
        this.activeClue = Clue;
        this.dir = Clue.dir;
        this.activeSquare = Clue.squares[0];
        this.activeReferences = Clue.references;
        this.sendOnEvents();
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

      this.changeActiveClue = function(dir, change) {
        // change will be +/- 1
        if (dir == this.dir) {
          change > 0 ? this.advanceActiveClue() : this.retreatActiveClue();
        }
        else {
          var newDir = dir == 'across' ? 'down' : 'across';
          this.setActiveClue(this.clues[newDir][0]);
        }
        return this;
      }

      this.focus = function() {
        if (this.activeSquare && this.activeSquare['$square']) {
          this.activeSquare['$square'].trigger('crossword-focus');
        }
      }

      this.setAnswer = function(letter) {

        if (letter.toLowerCase() !== letter) {
          // uppercase letters are for rebus
          // Is the existing answer uppercase? If so append. Otherwise, replace.
          if (this.activeSquare.answer && this.activeSquare.answer.toLowerCase() !== this.activeSquare.answer) {
            this.activeSquare.answer += letter;
          }
          else {
            this.activeSquare.answer = letter;
          }
          this.sendAnswerEvents();
        }
        else {
          this.activeSquare.answer = letter;
          this.sendAnswerEvents();
          if (letter === "") {
            this.retreatActiveSquare();
          }
          else {
            this.advanceActiveSquare();
          }
        }
      }

      this.cheat = function() {
        this.sendCheatEvents();
        this.setAnswer(this.activeSquare.fill);
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
              var realRefs = [];
              var refs = clues[dir][i].references
              for (var ref_index in refs) {
                realRefs.push(clues[refs[ref_index].dir][refs[ref_index].index]);
              }
              clues[dir][i].references = realRefs;
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

      this.sendOffEvents = function(){
        if (this.activeClue) {
          this.activeClue['$clue'].trigger('crossword-off');
          this.activeClue.squares.forEach(function(item, index){
            item['$square'].trigger('crossword-off');
          });
          if(this.activeReferences) {
            this.activeReferences.forEach(function(clue, index){
              clue['$clue'].trigger('crossword-off');
              clue.squares.forEach(function(item, index){
                item['$square'].trigger('crossword-off');
              });
            });
          }
        }
      }

      this.sendOnEvents = function(){
        if (this.activeClue && this.activeClue['$clue']) {
          this.activeClue['$clue'].trigger('crossword-active');
          this.activeClue.squares.forEach(function(item, index){
            item['$square'].trigger('crossword-highlight');
          });
          if(this.activeReferences) {
            this.activeReferences.forEach(function(clue, index){
              clue['$clue'].trigger('crossword-reference');
              clue.squares.forEach(function(item, index){
                item['$square'].trigger('crossword-reference');
              });
            });
          }
        }
        if (this.activeSquare && this.activeSquare['$square']) {
          this.activeSquare['$square'].trigger('crossword-active');
        }
      }

      this.sendAnswerEvents = function(){
        if (this.activeSquare && this.activeSquare['$square']) {
          this.activeSquare['$square'].trigger('crossword-answer', [this.activeSquare.answer]);
          if (this.activeSquare.answer !== null && this.activeSquare.answer.toUpperCase() !== this.activeSquare.fill.toUpperCase()) {
            this.activeSquare['$square'].trigger('crossword-error');
          }
          else {
            this.activeSquare['$square'].trigger('crossword-ok');
          }
          if (this.activeSquare.answer !== null && this.activeSquare.answer.length > 1 && this.activeSquare.answer.toLowerCase() !== this.activeSquare.answer) {
            this.activeSquare['$square'].trigger('crossword-rebus');
          }
          else {
            this.activeSquare['$square'].trigger('crossword-not-rebus');
          }
        }
      }

      this.sendCheatEvents = function(){
        if (this.activeSquare && this.activeSquare['$square']) {
          this.activeSquare['$square'].trigger('crossword-cheat');
          this.activeSquare.across['$clue'].trigger('crossword-cheat');
          this.activeSquare.down['$clue'].trigger('crossword-cheat');
        }
      }

      this.setActiveClue(this.clues.across[0]);

    }
  }

})(jQuery, Drupal, drupalSettings);
