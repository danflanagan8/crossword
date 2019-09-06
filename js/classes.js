(function ($, Drupal, drupalSettings) {

  Drupal.Crossword = {

    Square: function(data, answer, Crossword) {
      this.Crossword = Crossword;
      this.row = data.row;
      this.column = data.col;
      this.fill = "";
      if (data.fill !== null) {
        if (data.fill.length > 1) {
          this.fill = data.fill.toUpperCase(); //uppercase means rebus
        }
        else {
          this.fill = data.fill.toLowerCase();
        }
      }
      this.answer = answer ? answer : ""; // the user input
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

      this.connect = function($square) {
        this.$square = $square;
        Crossword.sendAnswerEvents(this);
      }

      this.hasError = function() {
        return this.answer && this.answer.toUpperCase() !== this.fill.toUpperCase();
      }

      this.isCorrect = function() {
        return !this.fill || (this.answer && this.answer.toUpperCase() === this.fill.toUpperCase());
      }

      this.isEmpty = function() {
        return this.fill && this.answer === "";
      }
    },

    Clue: function(data) {
      this.text = data.text;
      this.dir = data.dir;
      this.index = data.index;
      this.numeral = data.numeral;
      this.references = data.references; //starts as contstants. objects get added later
      this.squares = [];
      this.$clue = null;

      this.connect = function($clue) {
        this.$clue = $clue;
      }

      this.hasError = function() {
        for (var i = 0; i < this.squares.length; i++) {
          if (this.squares[i].hasError()) {
            return true;
          }
        }
        return false;
      }

      this.isCorrect = function() {
        for (var i = 0; i < this.squares.length; i++) {
          if (!this.squares[i].isCorrect()) {
            return false;
          }
        }
        return true;
      }

      this.getAriaCurrentString = function() {
        var aria = "";
        var countString = this.squares.length + " letters.";
        var blank = true;
        for (var i = 0; i < this.squares.length; i++) {
          if (this.squares[i].answer) {
            aria += this.squares[i].answer;
            blank = false;
          }
          else {
            aria += "blank";
          }
          aria += " ";
        }
        if (blank) {
          return countString;
        }
        else {
          aria = aria.substring(0, aria.length - 1);
          return "Answer: " + countString + " " + aria.toLowerCase();
        }
      }

      this.getAriaClueText = function() {
        return this.numeral + " " + this.dir + ". " + this.text.replace(/_{2,}/, "blank");
      }

      this.getAnswerLength = function() {
        return this.squares.length;
      }
    },

    Crossword: function(data, answers) {
      var Crossword = this;
      this.data = data;

      this.id = data.id;
      this.dir = 'across';
      this.activeSquare = {'row' : null, 'col': null};
      this.activeClue = null;
      this.activeReferences = [];
      this.answers = answers ? answers : emptyAnswers(); //the initial answers
      this.grid = makeGrid(this.answers);
      this.clues = makeClues();
      connectCluesAndSquares();
      this.stack = {
        'undo' : [],
        'redo' : [],
      };
      this.$crossword = null;
      this.solved = false;

      this.setActiveSquare = function(Square) {
        if (Square.fill !== "") {
          this.sendOffEvents();
          this.activeSquare = Square;
          this.activeClue = Square[this.dir];
          this.activeReferences = Square[this.dir] ? Square[this.dir].references : [];
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
        if (this.dir == 'across') {
          this.moveActiveSquare('right');
        }
        else {
          this.moveActiveSquare('down');
        }
        return this;
      }

      this.retreatActiveSquare = function() {
        if (this.dir == 'across') {
          this.moveActiveSquare('left');
        }
        else {
          this.moveActiveSquare('up');
        }
        return this;
      }

      this.advanceActiveClue = function() {
        if (this.activeClue) {
          if (this.clues[this.dir][this.activeClue.index + 1]) {
            this.setActiveClue(this.clues[this.dir][this.activeClue.index + 1]);
          }
        }
        else {
          this.setActiveClue(this.clues[this.dir][0]);
        }
        return this;
      }

      this.retreatActiveClue = function() {
        if (this.activeClue) {
          if (this.clues[this.dir][this.activeClue.index - 1]) {
            this.setActiveClue(this.clues[this.dir][this.activeClue.index - 1]);
          }
        }
        else {
          this.setActiveClue(this.clues[this.dir][0]);
        }
        return this;
      }

      this.changeActiveClue = function(dir, change) {
        // change will be +/- 1
        if (dir == this.dir) {
          change > 0 ? this.advanceActiveClue() : this.retreatActiveClue();
        }
        else {
          this.changeDir();
        }
        return this;
      }

      this.focus = function() {
        if (this.activeSquare && this.activeSquare['$square']) {
          this.activeSquare['$square'].trigger('crossword-focus');
        }
        return this;
      }

      this.setAnswer = function(letter, undo, redo) {
        if (letter.toLowerCase() !== letter) {
          // uppercase letters are for rebus
          // Is the existing answer uppercase and is the "letter" one letter? If so append. Otherwise, replace.
          if (this.activeSquare.answer && this.activeSquare.answer.toLowerCase() !== this.activeSquare.answer && letter.length == 1) {
            this.activeSquare.answer += letter;
          }
          else {
            if (!(undo || redo)) {
              this.stack.undo.push({'square' : this.activeSquare, 'letter' : this.activeSquare.answer});
              this.stack.redo = [];
            }
            this.activeSquare.answer = letter;
          }
          this.sendAnswerEvents(this.activeSquare);
        }
        else {
          if (!(undo || redo)) {
            this.stack.undo.push({'square' : this.activeSquare, 'letter' : this.activeSquare.answer});
            this.stack.redo = [];
          }
          this.activeSquare.answer = letter;
          this.sendAnswerEvents(this.activeSquare);
          if (!undo) {
            if (letter === "") {
              this.retreatActiveSquare();
            }
            else {
              this.advanceActiveSquare();
            }
          }
        }
        return this;
      }

      this.cheat = function() {
        this.sendCheatEvents(this.activeSquare);
        this.setAnswer(this.activeSquare.fill);
        return this;
      }

      this.undo = function() {
        if (this.stack.undo.length) {
          var oldState = this.stack.undo.pop();
          this.stack.redo.push({'square' : oldState.square, 'letter' : oldState.square.answer });
          this.setActiveSquare(oldState.square);
          this.setAnswer(oldState.letter, true);
        }
        return this;
      }

      this.redo = function() {
        if (this.stack.redo.length) {
          var oldState = this.stack.redo.pop();
          this.stack.undo.push({'square' : oldState.square, 'letter' : oldState.square.answer });
          this.setActiveSquare(oldState.square);
          this.setAnswer(oldState.letter, false, true);
        }
        return this;
      }

      this.reveal = function() {
        for (var row_index = 0; row_index < this.grid.length; row_index++) {
          for (var col_index = 0; col_index < this.grid[row_index].length; col_index++) {
            var Square = this.grid[row_index][col_index];
            if (Square.answer.toUpperCase() !== Square.fill.toUpperCase()) {
              Square.answer = Square.fill;
              this.sendCheatEvents(Square);
              this.sendAnswerEvents(Square);
            }
          }
        }
        return this;
      }

      this.getAnswers = function() {
        var answers = [];
        for (var $row_index = 0; $row_index < this.grid.length; $row_index++) {
          answers[$row_index] = [];
          for (var $col_index = 0; $col_index < this.grid[$row_index].length; $col_index++) {
            answers[$row_index][$col_index] = this.grid[$row_index][$col_index].answer;
          }
        }
        return answers;
      }

      this.clear = function() {
        this.setAnswers(emptyAnswers());
        return this;
      }

      this.setAnswers = function(answers) {
        this.stack.undo = [];
        for (var $row_index = 0; $row_index < this.grid.length; $row_index++) {
          for (var $col_index = 0; $col_index < this.grid[$row_index].length; $col_index++) {
            this.grid[$row_index][$col_index].answer = answers[$row_index][$col_index];
            this.sendAnswerEvents(this.grid[$row_index][$col_index]);
          }
        }
        return this;
      }

      this.isSolved = function() {
        for (var $row_index = 0; $row_index < this.grid.length; $row_index++) {
          for (var $col_index = 0; $col_index < this.grid[$row_index].length; $col_index++) {
            if (!this.grid[$row_index][$col_index].isCorrect()) {
              return false;
            }
          }
        }
        this.solved = true;
        return true;
      }

      this.countBlankSquares = function() {
        var count = 0;
        for (var $row_index = 0; $row_index < this.grid.length; $row_index++) {
          for (var $col_index = 0; $col_index < this.grid[$row_index].length; $col_index++) {
            if (this.grid[$row_index][$col_index].isEmpty()) {
              count++;
            }
          }
        }
        return count;
      }

      this.setClueResponse = function(Clue, response) {
        var responseIndex = 0;
        Clue.squares.forEach(function(square, index){
          if (index >= response.length || responseIndex >= response.length) {
            square.answer = "";
          }
          else {
            var answer = "";
            if (response[responseIndex].toLowerCase() != response[responseIndex]) {
              while (responseIndex < response.length && response[responseIndex].toLowerCase() != response[responseIndex]) {
                answer += response[responseIndex];
                responseIndex++;
              }
            }
            else {
              answer = response[responseIndex];
              responseIndex++;
            }
            square.answer = answer;
          }
        });
        this.sendClueResponseEvents(Clue);
      }

      /**
       * Functions that trigger events on dom elements.
       */
      this.sendClueResponseEvents = function(Clue) {
        Clue.squares.forEach(function(Square, index){
          Crossword.sendAnswerEvents(Square);
        });
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
        if (this.activeSquare && this.activeSquare['$square']) {
          this.activeSquare['$square'].trigger('crossword-off');
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

      this.sendAnswerEvents = function(Square){
        if (Square && Square['$square']) {
          Square['$square'].trigger('crossword-answer', [Square.answer]);
          if (Square.hasError()) {
            Square['$square'].trigger('crossword-error');
          }
          else {
            Square['$square'].trigger('crossword-ok');
          }
          if (Square.answer.length > 1 && Square.answer.toLowerCase() !== Square.answer) {
            Square['$square'].trigger('crossword-rebus');
          }
          else {
            Square['$square'].trigger('crossword-not-rebus');
          }

          // now the clues
          if (Square.down) {
            Square.down['$clue'].trigger('crossword-aria-update');
            if (Square.down.hasError()) {
              Square.down['$clue'].trigger('crossword-error');
            }
            else {
              Square.down['$clue'].trigger('crossword-ok');
            }
          }
          if (Square.across) {
            Square.across['$clue'].trigger('crossword-aria-update');
            if (Square.across.hasError()) {
              Square.across['$clue'].trigger('crossword-error');
            }
            else {
              Square.across['$clue'].trigger('crossword-ok');
            }
          }
        }
        if (!this.solved && this.isSolved()) {
          if (this.$crossword) {
            this.$crossword.trigger('crossword-solved');
          }
        }
      }

      this.sendCheatEvents = function(Square){
        if (Square && Square['$square']) {
          Square['$square'].trigger('crossword-cheat');
          if (Square.across && Square.across['$clue']) {
            Square.across['$clue'].trigger('crossword-cheat');
          }
          if (Square.down && Square.down['$clue']) {
            Square.down['$clue'].trigger('crossword-cheat');
          }
        }
      }

      /**
       * Internal functions for initialization.
       */
      function emptyAnswers() {
        var grid = Crossword.data.puzzle.grid;
        var answers = [];
        for (var row_index = 0; row_index < grid.length; row_index++) {
          answers.push([]);
          for (var col_index = 0; col_index < grid[row_index].length; col_index++) {
            answers[row_index].push('');
          }
        }
        return answers;
      }

      function makeGrid(answers) {
        var grid = [];
        var data_grid = Crossword.data.puzzle.grid;

        // start by creating objects
        for (var row_index = 0; row_index < data_grid.length; row_index++) {
          var row = [];
          for (var col_index = 0; col_index < data_grid[row_index].length; col_index++) {
            row[col_index] = new Drupal.Crossword.Square(data_grid[row_index][col_index], answers[row_index][col_index], Crossword);
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

      // A funny thing for initialization that doesn't have anywhere nice to go.
      this.setActiveClue(this.clues.across[0]);

    }
  }

})(jQuery, Drupal, drupalSettings);
