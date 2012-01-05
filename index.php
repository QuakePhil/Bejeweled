
<style>
#gametable td
	{
	width: 20px;
	height: 20px;
	}
#inspect
	{
	font-size: smaller;
	}
</style>
<script type="text/javascript" src="jquery.js"></script> 
<html>
<table border=0><tr height=400px valign=top><td>
<span id="game">
</span>
</td><td>
<span id="inspect">
</span>
</td></tr></table>
<script type="text/javascript">

// 2011-12-27 - Initial code with ai_move
// 2011-12-28 - Added notation[], ai move inspect; "fixed" random number generator for ai_move
// 2011-12-29 - Added history traversal
// 2011-12-30 - Bugfixes, one cell in adjacent pointed to 64 = bad, apply_gravity worked on row 8 = bad


// todo make ai not cheat!
// todo tiebreak on equal moves, by counting sets of 2 instead of sets of 3
// todo fix infinite loop bug
// todo implement explosive gems and hypercubes

var width = 8;
var height = 8;
var basic_jewels = 7;
var bgcolor = ['red','blue','green','cyan','yellow','magenta','white'];
var jewels = [];
var adjacent = [];
var notation = [];
var score = 0;
var moves_made = 0;
var fake_rand = 0;
var history = [];
var history_position = 0;

var interactive = 1;
var timing = 100;

function precompute()
	{
	var toconcat = [];
	for (var i = 0; i < width * height; ++i)
		{
		if (adjacent[i] == undefined)
			adjacent[i] = [];

		j = String.fromCharCode('a'.charCodeAt(0) + i % width);
		k = height - Math.floor(i / width);
		notation[i] = j + k; // chess board style notation :)

		if (i % width == width - 1)
			toconcat = [i - 1];
		else if (i % width == 0)
			toconcat = [i + 1];
		else
			toconcat = [i - 1, i + 1];
		adjacent[i] = adjacent[i].concat(toconcat);

		if (i < width)
			toconcat = [i + width];
		else if (i >= width * height - width)
			toconcat = [i - width];
		else
			toconcat = [i - width, i + width];

		adjacent[i] = adjacent[i].concat(toconcat);
		}
	}
precompute();

function random_jewel(really_random)
	{
	if (really_random) // for new_game()
		return Math.floor(Math.random() * basic_jewels)
	else
		{ // for ai :)
		fake_rand = (fake_rand + 1) % basic_jewels;
		return fake_rand;
/* <arm_waving>
   Even though only the initial board is random, the game remains random anyway... I think.
   Consider a set of random numbers.  Now start going through this set, picking one number at
   a time, at random, and replacing it with the next number in a (uniformly distributed) sequence
   and the set will still be random.  Of course I am assuming here that the moves made on the
   board are analogous to being random, and I think that's a safe assumption :)
   </arm_waving>
*/
		}
	}

function arr2obj(arr) // convert array to object
	{
	var obj = {};
	for (var i = 0; i < arr.length; ++i)
		obj[arr[i]] = '';
	return obj;
	}

function swap_jewels(a,b)
	{
	var temp = jewels[a];
	jewels[a] = jewels[b];
	jewels[b] = temp;
	}

var old_move = -1;
function move(k)
	{
	if (interactive == 0) return;

	if (old_move == -1)
		old_move = k;
	else
		{
		if (old_move in arr2obj(adjacent[k])) // adjacent?
			{
			swap_jewels(k, old_move);
			if (splode(1) == 0) // zero energy move
				{
				alert('Nope :/');
				swap_jewels(k, old_move);
				}
			else
				{
				interactive = 0;
				splode();
				if (timing > 0)
					{
					setTimeout('gravity()', timing);
					display_board();
					}
				else
					gravity();
				}
			}
		old_move = -1;
		++moves_made;
		}
	}

function count_moves(notate)
	{
	var moves = [];
	for (var i = 0; i < adjacent.length; ++i)
		{
		for (var j = 0; j < adjacent[i].length; ++j) if (i < adjacent[i][j]) // every move has a mirror, ignore it
			{
			swap_jewels(i, adjacent[i][j]);
			if (splode(1) > 0)
				{
				if (notate)
					moves.push(notation[i] + ' to ' + notation[adjacent[i][j]]);
				else
					moves.push(i + ' to ' + adjacent[i][j]);
				}
			swap_jewels(i, adjacent[i][j]);
			}
		}
	return moves;
	}

function display_board()
	{
	var moves = count_moves();
	var game = 'Score: ' + score + ', Moves made: ' + history_position + ', SPM: ' + Math.round(score / moves_made) + '</br>';
	game += '<span title="' + count_moves(1) + '">Moves on the board: ' + moves.length + '</span>';
	var k = 0;

	game += '<hr><table id=gametable border=1 cellpadding=0 cellspacing=0>';

	for (var row = 0; row < height; ++row)
		{
		game += '<tr>';
		for (var column = 0; column < width; ++column)
			{
			if (jewels[k] == -1)
				{
				game += '<td>X</td>';
				}
			else
				{
				game += '<td title="' + notation[k] + '" onclick=move(' + k 
					+ ') bgcolor=' + bgcolor[jewels[k]] + '>';
				game += '&nbsp;'; //jewels[k];
				game += '</td>';
				}
			++k;
			}
		game += '</tr>';
		}

	game += '</table>';

	$('#game').html(game);
	}

function new_game()
	{
	jewels = [];

	for (var i = 0; i < width * height; ++i)
		jewels[i] = random_jewel(1);

//jewels = 
//[4,5,6,1,6,0,1,5,5,6,0,5,4,0,1,4,6,4,0,1,2,5,6,3,5,3,4,5,6,3,1,5,4,2,2,3,4,5,6,0,3,5,1,4,1,2,4,5,4,3,2,0,5,3,4,0,5,2,3,5,6,4,2,1];

	// guarantee a quiet board
	while (splode())
		{
		while (apply_gravity()) {;}
		}

	score = 0;
	moves_made = 0;
	history = [];
	history[0] = [fake_rand, jewels.slice(0)];
	history_position = 0;
	display_board();
	}

new_game();

// splode deletes (sets to -1) any jewel which matches 3 or more
// vertically or horizontally
function splode(count_only, stability_factor)
	{
	if (stability_factor == undefined)
		stability_factor = 2; // a line of jewels 1 more than this will explode

	var splode = [];
	var previous_jewel;
	var same;
	var energy = 0;

	for (var i = 0; i < width * height; ++i)
		splode[i] = 0;

	for (var row = 0; row < height; ++row)
		{
		previous_jewel = -1;
		same = 0;
		for (var column = 0; column < width; ++column)
			{
			var k = column + row * width;
			if (previous_jewel != -1 && previous_jewel == jewels[k])
				++same;
			else
				same = 0;
			if (same >= stability_factor)
				{
				for (var l = 0; l <= stability_factor; ++l)
					splode[k-l] = 1;
//				splode[k-2] = 1;
//				splode[k-1] = 1;
//				splode[k] = 1;
				}

			previous_jewel = jewels[k];
			}
		}

	for (var column = 0; column < width; ++column)
		{
		previous_jewel = -1;
		same = 0;
		for (var row = 0; row < height; ++row)
			{
			var k = row * width + column;
			if (previous_jewel != -1 && previous_jewel == jewels[k])
				++same;
			else
				same = 0;
			if (same >= stability_factor)
				{
				for (var l = 0; l <= stability_factor; ++l)
					splode[k-(l*width)] = 1;
//				splode[k-width-width] = 1;
//				splode[k-width] = 1;
//				splode[k] = 1;
				}

			previous_jewel = jewels[k];
			}
		}

	for (var i = 0; i < width * height; ++i)
		{
		if (count_only != 1 && splode[i] == 1)
			jewels[i] = -1;
		energy += splode[i];
		}

	if (count_only != 1) // commented pow out for now for SPM to make a little more sense
		score += energy; // Math.pow(5, energy);

	return energy;
	}

// apply_gravity applies gravity, sinking columns of jewels down
// where there are any missing spots (-1) and repopulating them from the
// top as well
function apply_gravity()
	{
	var row_delta;
	var continue_gravity = 0;
	for (var column = 0; column < width; ++column)
		{
		row_delta = 0;
		for (var row = height - 1; row >= 0; --row)
			{
			k = row * width + column;
			if (jewels[k] == -1)
				{
				row_delta = width;
				}
			if (jewels[k - row_delta] == undefined)
				jewels[k] = random_jewel();
			else
				jewels[k] = jewels[k - row_delta];

			if (jewels[k] == -1)
				continue_gravity = 1;
			}
		}
	return continue_gravity;
	}

function reduce()
	{
	if (interactive == 0) return;

	interactive = 0;
	splode();
	if (timing > 0)
		{
		display_board();
		setTimeout('gravity()', timing);
		}
	else
		gravity();
	}

function gravity()
	{
	j = apply_gravity();
	if (j == 0)
		{
		if (splode(1) == 0)
			{
			history = history.slice(0, history_position+1); // if we are in the middle of a history, dump the tail
			history[++history_position] = [fake_rand, jewels.slice(0)];

			interactive = 1;
			display_board();

			// todo: don't trigger for non-ai moves
			if ($('#gogo').attr('checked'))
				setTimeout('ai_move()', timing);
			}
		else
			{
			splode();
			if (timing > 0)
				{
				setTimeout('gravity()', timing);
				display_board();
				}
			else
				gravity();
			}	
		}
	else
		{
		if (timing > 0)
			{
			display_board();
			setTimeout('gravity()', timing);
			}
		else
			gravity();
		}
	}

function energy()
	{
	alert('Energy: ' +splode(1));
	}

function ai_move(inspect)
	{
	if (interactive == 0) return;

	old_move = -1;

	var moves = count_moves();

	if (moves.length == 0) return;

	var best_move_count = 0;
	var best_doubles_count = 0;
	var best_score = 0;
	var best_whattomax = 0; // dumb, but oh well
	var best_whattomax2 = 0;
	var best_move;

	var inspected = '';

	for (var i = 0; i < moves.length; ++i)
		{
		var old_fake_rand = fake_rand;
		var old_jewels = jewels.slice(0);
		var old_score = score;
		var old_moves_made = moves_made;
		var old_timing = timing;
		var old_history = history.slice(0);
		var old_history_position = history_position;

		timing = 0;
		score = 0;

		var the_move = moves[i].split(' to ');
		inspected += 'move[' + (i + 1) + ']: ' + notation[the_move[0]] + ' to ' + notation[the_move[1]];

		move(the_move[0]);
		move(the_move[1]);

		var the_moves = count_moves();
		var the_move_count = the_moves.length;
		var the_doubles_count = splode(1, 1);

		inspected += ', moves = ' + the_move_count + ', doubles = ' + the_doubles_count + ', score = ' + score;

		var whattomax = $("input[type='radio']:checked").val();
		var whattomax2 = 0;
		if (whattomax == 'score')
			{
			whattomax = score;
			whattomax2 = the_move_count;
			}
		else
			{
			whattomax = the_move_count;
			whattomax2 = the_doubles_count;
			}

		//if (the_move_count > best_move_count)
		if (whattomax > best_whattomax)
			{
			if (best_whattomax > 0)
				inspected += ', ' + (whattomax - best_whattomax) + ' more ' +
					(whattomax - best_whattomax == 1 && $("input[type='radio']:checked").val() == 'moves'
						? $("input[type='radio']:checked").val().substr(0, 4)
						: $("input[type='radio']:checked").val() );

			best_whattomax = whattomax;
			best_whattomax2 = whattomax2;
			best_move = the_move;
			best_move_count = the_move_count;
			best_doubles_count = the_doubles_count;
			best_score = score;
			}
		else if (whattomax == best_whattomax && whattomax2 > best_whattomax2) // tiebreak
			{
			inspected += ', tie-break';

			best_whattomax = whattomax;
			best_whattomax2 = whattomax2;
			best_move = the_move;
			best_move_count = the_move_count;
			best_doubles_count = the_doubles_count;
			best_score = score;
			}

		inspected += '<br/>';

		fake_rand = old_fake_rand;
		jewels = old_jewels.slice(0);
		score = old_score;
		moves_made = old_moves_made;
		timing = old_timing;
		history = old_history.slice(0);
		history_position = old_history_position;
		}

	//if (inspect == 1)
		{
		inspected += 'best move: ' + notation[best_move[0]] + ' to ' + notation[best_move[1]];
		$('#inspect').html(inspected);
		display_board();
		}
	if (inspect != 1)
		{
		move(best_move[0]);
		move(best_move[1]);
		}
	}

function history_traverse(direction)
	{
	if (history_position + direction < 0 || history_position + direction >= history.length)
		{
		alert(direction == -1 ? 'Already at start' : 'Already at end');
		return;
		}
	history_position += direction;
	var history_slice = history[history_position].slice(0);
	fake_rand = history_slice[0];
	jewels = history_slice[1].slice(0);
	display_board();
	}

</script>
<form>
<input type=button value=New onclick=new_game()>
<!-- <input type=button value=Reduce onclick=reduce()>
<input type=button value=Energy onclick=energy()> -->
<input type=button value="AI" onclick=ai_move()>
<input type=button value="Inspect" onclick=ai_move(1)>
<input type=button value="<<" onclick=history_traverse(-1)>
<input type=button value=">>" onclick=history_traverse(1)>
<label for=gogo><input type=checkbox id=gogo> AI keeps playing</label></br>
<label for=maxmoves><input id=maxmoves type=radio name=whattomax value=moves checked=1>Maximize moves</label>
<label for=maxscore><input id=maxscore type=radio name=whattomax value=score>Maximize score</label>
<hr>
AI: For every move X, calculate the number of moves Y in the resulting position.  The move played 
will be the one with the largest Y (tie-break: largest score).  Some insight on trying to pick the 
smallest Y (trying to lose the game as fast as possible) showed that some positions can go to lost very 
quickly, and some other positions can linger on in the single-digit Y range for a long time, suggesting 
that there are certain features/jewel patterns that can further be searched for in order to find the 
latter kind of position as a worst case scenario, rather than the former.
</form>
</html>
