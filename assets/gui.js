/*TODO:
 * Glitch pri zahrani karty
 * zjednotit backend messaging
 * game server do CRONu
 * connection quality checking (ping/pong)
 * statistiky
 * sprehladnit (rozumej prerobit) gui kod
*/

/* Message reference
 * m_cmd - name (params)
 *
 * Game visuals:
 * 100 Text message (String text)
 *
 * 101 Action card play (int player_id, int card_id, int river_pos, mixed extras, int[] died_pos)
 *
 * 102 Unprotect duck (int[] positions) {end of KACHNI_UNIK}
 *
 */

var sorting_rosambo = false;
var rosambo_river_backup;
var rosambo_hand_backup;
var first_show = true;
var has_played_card = false;
var target_shift = 159;

// color table
var colors = {
  '-1': 'Bez farby',
  0: 'Fialová',
  1: 'Zelená',
  2: 'Modrá',
  3: 'Oranžová',
  4: 'Žltá',
  5: 'Ružová'
};

function subscribe(gameId) {
  window.gid = gameId;
  ws.exec('gameJoin', {
    gameId: gameId
  });
  switch_game_phase('beforeGame');
  ws.exec('gameDetails');
  ws.exec('chatLoad');
  window.history.pushState(null, '', '?gid='+gameId);
}

function unsubscribe() {
  ws.exec('gameLeave');
  back_to_gameList();
}

function back_to_gameList() {
  switch_game_phase('noGame');
  ws.exec('gameList');
  window.history.pushState(null, '', '?');
}

function game_start() {
  ws.exec('gameStart');
  switch_game_phase('inGame');
}

function game_new() {
  var title = window.prompt("Zadaj názov hry");
  if ((title === null) || (title.length == 0)) return;

  ws.exec('gameNew', {
    title: title
  });

  ws.exec('gameDetails');
}

function set_color(e) {
  ws.exec('setColor', {
    color: e.value
  });
}

function card_play_click(card_id, param0, param1, param2) {
  var animation_move=$(null).promise();
  var animation_shift=$.Deferred();

  has_played_card = true;

  // card movement
  var card_alias = 'h'+card_id;
  var card_type = get_action_card_type(card_alias);
  if (card_type==10) { // KACHNI_UNIK
    animation_move=card_move(card_alias, 'r'+param0);
  } else {
    animation_move=card_move(card_alias, 'p0');
  }

  // shift cards in hand after the card movement is done
  animation_move.always(function() {
    $(resolve_css_selector(card_alias)).hide('slow', function() {
      $('#hand-inner').append(this);
      $(this).show();
    }).promise().then(animation_shift.resolve, animation_shift.reject);
  });

  ws.exec('cardPlay', {
    card_id: card_id,
    param0: param0,
    param1: param1,
    param2: param2
  });

//    $.when(animation_move, animation_shift).always(function() {
//      data_ready(data);
//    });
}

// vyber prvu kartu a ponukni druhe karty
function card_select(card, moves, orig_card_index) {
  // cistenie vysvietenych kariet na stole
  $('#river div').removeClass('possible_move selected_move').off('click');
  $('#pile div').removeClass('possible_move').off('click');

  card.addClass('selected_move');

  for (var i=0; i<6; i++) {
    // vysvietit a zaroven pridat onClick handler
    if (moves[i]) $('#river div:eq('+i+')').addClass('possible_move').click(function(e){
      card_play_click(orig_card_index, card.index(), $(this).index(), '');
    });
  }
}

// Detach targets from their divs in river to allow duck movement without them.
// Optionaly move targets verticaly to allow for playing ROSAMBO
function detach_targets(raise) {
  var pos_river=$('#river').offset();
  $('#river>div>img.target').each(function(index, elem) {
    var pos=$(elem).offset();

    $('#river').append($(elem));

    $(elem).css({left: pos.left-pos_river.left, top: pos.top-pos_river.top});
    if (raise)
      $(elem).animate({
        top: '-='+target_shift
      });
  });
}

// Reattach targets after the manipulation with ducks is completed.
// Optionaly move targets verticaly to undo their positioning during detach
function reattach_targets(lower) {
  $('#river>img.target').each(function(index, elem) {
    if (lower)
      $(elem).animate({
        top: '+='+target_shift
      }, function() {
        $(elem).removeAttr('style');
        var t_index = $(elem).attr('id').split('_')[1];
        $('#river div:eq('+t_index+')').append($(elem));
      });
    else {
      $(elem).removeAttr('style');
      var t_index = $(elem).attr('id').split('_')[1];
      $('#river div:eq('+t_index+')').append($(elem));
    }
  });
}

// move target from src position to dst
// if src or dst is -1, then create or remove the target
// src and dst are in -1..5
function move_target(src, dst) {
  if (src==-1) {
    var dst_pos=$(resolve_css_selector('r'+dst)).position();
    var tgt=$(resolve_card_contents('t_'+dst)).css({left: dst_pos.left+6, top: dst_pos.top+4-target_shift}).hide();
    $('#river').append(tgt);
    return tgt.fadeIn({
      queue:false,
      duration:'slow'
    }).animate({
      top: '+='+target_shift
    }).promise();
  }
  else if (dst==-1) {
    return $('#target_'+src).fadeOut({
      queue:false,
      duration:'slow'
    }).animate({
      top: '-='+target_shift
    }, function() {
      $('#target_'+src).remove();
    }).promise();
  }
  else {
    var delta=(dst-src)*162;
    return $('#target_'+src).attr('id','target_'+dst).animate({
      left: '+='+delta
    }).promise();
  }
}

function rosambo_play(orig_card_index) {
  // detach and raise targets
  detach_targets(true);

  // add id to every card to know its old position after sorting_rosambo
  // remove titles as they slow down the moving process
  $('#river div').attr('id','');
  $('#river div').each(function(idx, e){
    $(e).attr({
      id: 'river_'+idx,
      title: ''
    });
  });

  $('#river').sortable();
  sorting_rosambo = true;

  var hand_css_selector = resolve_css_selector('h'+orig_card_index);

  rosambo_hand_backup = $(hand_css_selector).html();
  rosambo_river_backup = $('#river').html();

  $(hand_css_selector).html('<h2>Potvrdiť nové pozície</h2>').off('click').click(function(){
    $(this).html(rosambo_hand_backup);

    $('#river').sortable('destroy');
    sorting_rosambo = false;

    var perm="";
    for(var i=0; i<6; i++) {
      perm+=$('#river div:eq('+i+')').attr('id').split('_')[1]+" ";
    }

    reattach_targets(true);

    card_play_click(orig_card_index, 0, 0, perm);
  });
}

function get_action_card_type(card) {
  if (card[0]!='h') return -1;
  var card_obj=$(resolve_css_selector(card)+' img');
  if (card_obj.length == 0) return -1;

  var parts=card_obj.attr('src').split('card');
  return parts[1].split('.')[0]*1;
}

// convert short-hand notation to full css selector
// first char: h=hand, r=river, p=pile, d=deck, l=pLayer, t=target
// second char: 0-2 for hand, 0-5 for river, 0 for pile and deck, 0-5 for player, 0-5 for target
function resolve_css_selector(x) {
  var selector;
  switch(x[0]) {
    case 'h': selector='#hand-inner div:eq('+x[1]+')'; break;
    case 'r': selector='#river div:eq('+x[1]+')'; break;
    case 'p': selector='#pile div'; break;
    case 'l': selector='#lives-row td.player:eq('+x[1]+') span.duck-lives'; break;
    case 't': selector='#target_'+x[1]; break;
    case 'd':
    default: selector='#deck div'; break;
  }
  return selector;
}

// convert short-hand notation to full element
// format: T_NUM
// T: d=duck, a=river action_card, p=pile action_card, e=empty card, t=target, m=mini-duck
// NUM: -1 - 5 for duck (-1=water), 0-15 for action card, 0-5 for target, 0-5 for mini-duck
function resolve_card_contents(x) {
  var xdata=x.split('_');
  var elem;
  switch(xdata[0]) {
    case 'd': elem='<div class="any-card"><img class="duck" src="assets/i/duck'+xdata[1]+'.png" alt="duck"></div>'; break;
    case 'a': elem='<div class="any-card"><img class="actioncard" src="assets/i/card'+xdata[1]+'.png" alt="action"></div>'; break;
    case 'p': elem='<div class="pile-card"><img class="actioncard" src="assets/i/card'+xdata[1]+'.png" alt="action"></div>'; break;
    case 't': elem='<img id="target_'+xdata[1]+'" class="target" src="assets/i/target.png" alt="target">'; break;
    case 'm': elem='<img class="miniduck" src="assets/i/duck'+xdata[1]+'.png" alt="duck">'; break;
    case 'e':
    default: elem='<div class="any-card placeholder"></div>'; break;
  }
  return elem;
}

function card_move(src, dst) {
  var src_obj=$(resolve_css_selector(src));
  var dst_sel=resolve_css_selector(dst);
  var dst_obj=$(dst_sel);

  var p1 = src_obj.offset();
  var p2 = dst_obj.offset();

  return src_obj.animate({
    left: p2.left-p1.left,
    top: p2.top-p1.top
  }, 'slow', function() {
    src_obj.attr('class', dst_obj.attr('class')).removeAttr('style').after(resolve_card_contents('e_0')).replaceAll(dst_sel);
  }).promise();
}

// perform TURBOKACHNA, LEHARO, CHVATAM
function card_swap(src, dst) {
  if (src==dst) return $(null).promise();

  var anim=[];

  var src_obj=$(resolve_css_selector('r'+src));
  var dst_obj=$(resolve_css_selector('r'+dst));

  anim.push(src_obj.css('z-index', 10).animate({
    left: '+='+((dst-src)*162)
  }, 'slow').promise());

  var start, end, delta;
  if (src<dst) {
    start=src+1; end=dst+1; delta='-=162';
  } else if (dst<src) {
    start=dst; end=src; delta='+=162';
  }

  for (var i=start; i<end; i++) {
    anim.push($(resolve_css_selector('r'+i)).animate({
      left: delta
    }, 'slow').promise());
  }

  return $.when.apply($, anim).always(function() {
    if (src<dst) src_obj.insertAfter(dst_obj);
    else if (dst<src) src_obj.insertBefore(dst_obj);
    $('#river div').removeAttr('style');
  }).promise();
}

// perform ZIVY_STIT
function card_hide(src, dst, new_duck) {
  var features = get_duck_features(dst);

  var defer1=$.Deferred();
  var anim2=$(null).promise();

  var src_obj=$(resolve_css_selector('r'+src));
  var dst_obj=$(resolve_css_selector('r'+dst));

  src_obj.css('z-index', 0).animate({
    left: '+='+((dst-src)*162)
  }, 'slow').hide('slow', function() {
    $(this).detach();

    // get the new duck, initialize as hidden, attach to the river, slowly show, then notify our deferred object which was returned years ago
    $(resolve_card_contents('d_'+new_duck)).hide().appendTo('#river').show('slow').promise().then(defer1.resolve, defer1.reject);
  });

  if (features==0) { // Duck::ONLY -> show miniduck and change to Duck::DUCK
    var miniduck=src_obj.children('img.duck').attr('src').split('duck')[1].split('.')[0]*1;
    var duck=$(resolve_card_contents('m_'+miniduck)).hide();
    dst_obj.removeClass('duck-only').addClass('duck-duck').append(duck.fadeIn('slow'));
    anim2=duck.promise();
  }
  else if (features==1) { // Duck::PROT -> replace actioncard with duck and change to Duck::DUCK_PROT
    var miniduck=src_obj.children('img.duck').attr('src').split('duck')[1].split('.')[0]*1;
    anim2=dst_obj.children('img.actioncard').css('z-index', 5).css({
      position: 'absolute',
      left: 0,
      top: 0
    }).fadeOut('slow', function() {
      $(this).detach();
    }).promise();
    dst_obj.removeClass('duck-prot').addClass('duck-duckprot').prepend($(resolve_card_contents('d_'+miniduck)).html());
  }

  return $.when(defer1.promise(), anim2);
}

// perform ROSAMBO
function card_shuffle(layout) {
  var anim=[];

  for (var i=0; i<6; i++) {
    if (i==layout[i]) continue;
    anim.push($(resolve_css_selector('r'+layout[i])).animate({
      left: '+='+((i-layout[i])*162)
    }, 800).promise());
  }

  return $.when.apply($, anim).always(function() {
    var e=[];

    for (var i=0; i<6; i++) e.push($(resolve_css_selector('r'+layout[i])));
    for (i=0; i<6; i++) e[i].appendTo('#river').removeAttr('style');
  }).promise();
}

// perform KACHNI_POCHOD
function return_first_duck(new_duck) {
  var defer=$.Deferred();

  var src_obj=$(resolve_css_selector('r0'));
  var dst_obj=$(resolve_css_selector('d0'));

  var p1 = src_obj.offset();
  var p2 = dst_obj.offset();

  src_obj.animate({
    top: p2.top-p1.top
  }, 400).animate({
    left: p2.left-p1.left
  }, 800).hide('slow', function() {
    $(this).detach();
    // get the new duck, initialize as hidden, attach to the river, slowly show, then notify our deferred object which was returned years ago
    $(resolve_card_contents('d_'+new_duck)).hide().appendTo('#river').show('slow').promise().then(defer.resolve, defer.reject);
  });

  // this function hereby promises to resolve this deferred object as soon as the last animation is over. Signed in Malmo, 14.8.2015
  return defer.promise();
}

// perform DIVOKEJ_BILL, VYSTRELIT, JEJDA_VEDLE, (part of) DVOJITA_TREFA
// features contains info about hidden ducks / protection
function kill_duck(position, new_duck) {
  var features = get_duck_features(position);
  var defer=$.Deferred();
  var src_obj=$(resolve_css_selector('r'+position));

  if (features>=2) { // Duck::DUCK -> create duck underneath; Duck::DUCK_PROT -> create PROT underneath
    var miniduck=src_obj.children('img.miniduck').attr('src').split('duck')[1].split('.')[0]*1;
    var new_obj=src_obj.clone();
    new_obj.css('z-index', 50).css({
      position: 'absolute',
      left: src_obj.position().left,
      top: src_obj.position().top
    }).appendTo('#river');

    if (features==2)
      src_obj.replaceWith($(resolve_card_contents('d_'+miniduck)).addClass('duck-only'));
    else if (features==3)
      src_obj.replaceWith($(resolve_card_contents('a_10')).addClass('duck-prot').append(resolve_card_contents('m_'+miniduck)));

    src_obj = new_obj;
  }

  src_obj.hide('slow', function() {
    $(this).detach();
    if (features>=2) { // Duck::DUCK, Duck::DUCK_PROT -> do not shift ducks
      defer.resolve();
    } else {	// Duck::ONLY, (Duck::PROT) -> shift ducks (function does not get called with Duck::PROT)
      // get the new duck, initialize as hidden, attach to the river, slowly show, then notify our deferred object which was returned years ago
      $(resolve_card_contents('d_'+new_duck)).hide().appendTo('#river').show('slow').promise().then(defer.resolve, defer.reject);
    }
  });

  return defer.promise();
}

// perform KACHNI_TANEC
function return_all_ducks(new_duck) {
  var defer=[];

  function tmp(i) {
    var src_obj=$(resolve_css_selector('r'+i));

    src_obj.hide('slow', function() {
      $(this).detach();
      // get the new duck, initialize as hidden, attach to the river, slowly show, then notify our deferred object which was returned years ago
      $(resolve_card_contents('d_'+new_duck[i].color)).hide().appendTo('#river').show('slow').promise().then(defer[i].resolve, defer[i].reject);
    });
  }

  for (var i=0; i<6; i++) {
    defer.push($.Deferred());
    tmp(i);
  }
  return $.when.apply($, defer);
}

function get_duck_features(position) {
  var d=$(resolve_css_selector('r'+position));
  if (d.hasClass('duck-duckprot')) return 3;
  else if (d.hasClass('duck-duck')) return 2;
  else if (d.hasClass('duck-prot')) return 1;
  else return 0;
}

// create new card on position src and move it to dst
// return Promise object for observing the animation
// optionaly replace src card while obscured by the spawned card
function spawn_card(card, src, dst, title, new_src) {
  if (typeof(title)==='undefined') title = '';

  var src_sel=resolve_css_selector(src);
  var src_pos=$(src_sel).offset();
  var dst_sel=resolve_css_selector(dst);
  var dst_pos=$(dst_sel).offset();
  var body_pos=$('body').offset();

  var new_card=$(resolve_card_contents(card)).css({
    position: 'absolute',
    top: src_pos.top-body_pos.top,
    left:	src_pos.left-body_pos.left,
    margin: 0
  }).attr('title', title).hide();

  $('body').append(new_card);

  return new_card.show('slow', function() {
    if (typeof(new_src)!=='undefined') {
      $(src_sel).replaceWith(new_src);
    }
  }).animate({
    top: dst_pos.top-body_pos.top,
    left: dst_pos.left-body_pos.left
  }, function(){
    new_card.attr('class', $(dst_sel).removeClass('placeholder').attr('class')).removeAttr('style').replaceAll(dst_sel);
  }).promise();
}

function process_targets(data) {
  var added=[];
  var deleted=[];
  for(var i in data) {
    if (($('#target_'+i).length>0) && (!data[i].target)) deleted.push(i);
    else if (($('#target_'+i).length==0) && (data[i].target)) added.push(i);
  }

  if (added.length==0) { 	// remove targets
    for (var i in deleted) move_target(deleted[i], -1);
    return $('.target').promise();
  } else if (deleted.length==0) { // add targets
    for (var i in added) move_target(-1, added[i]);
    return $('.target').promise();
  } else { // move one target
    return move_target(deleted[0], added[0]);
  }
  //return $(null).promise();
}

function show_game_list(data) {
  $('#tb-game-list').empty();
  for (var game_id in data) {
    if (!data.hasOwnProperty(game_id)) continue;
    var game = data[game_id];
    var gameline = $('<tr><td class="game-title"></td><td class="game-players"></td><td class="game-active"></td></tr>');
//    gameline.find('.game-id').text(game_id);
    gameline.find('.game-title').html('<a href="javascript:subscribe('+game_id+')">'+game.title+'</a>');
    gameline.find('.game-players').text(game.players);
    gameline.find('.game-active').text(game.active);
    $('#tb-game-list').append(gameline);
  }
}

// received data from server
function data_ready(data) {
  // check for null or non-array
  if (data && (data.constructor === Object)) {
    detach_targets(false);

    switch(data.cmd) {
      case 'gameList':
        show_game_list(data.args);
        break;
      case 'setColor':
        $('select[data-user="'+data.args.userId+'"]').val(data.args.color);
        break;
      case 'gameNew':
        var game_id = data.args.gameId;
        switch_game_phase('beforeGame');
        window.history.pushState(null, '', '?gid='+game_id);
        break;
      case 'gameJoin':
        append_player({
          id: data.args.userId,
          name: data.args.userName,
          color: -1
        });
        break;
      case 'gameLeave':
        remove_player(data.args.userId);
        break;
      case 'gameStart':
        first_show = true;
        break;
      case 'chatLoad':
        $('#message-box').empty();
        for(var i in data.args) {
          if (!data.args.hasOwnProperty(i)) continue;
          $('#message-box').append('<div>'+data.args[i]+'</div>');
        }
        $('#message-box').animate({
          scrollTop: $('#message-box')[0].scrollHeight
        }, 1000);
        break;
      case 'chat':
        $('#message-box').append('<div>'+data.args.text+'</div>').animate({ scrollTop: $('#message-box')[0].scrollHeight}, 1000);
        break;
      case 'gameDetails':
        if (data.args.active == 1) {
          switch_game_phase('inGame');
        }
        process_messages(data.args).then(
          function () { // done function (process_messages finished all async operations)
            process_state(data.args).always(function () {
              reattach_targets(false);
            });
          },
          function () { // fail function (process_messages aborted)
            $('#message-input').attr('placeholder', 'Správy sú nedostupné').prop('readonly', true);
            $('#river').html('<h2>Neplatná hra</h2>');
          }
        );
        break;
    }
  }
}

function process_state(data) {
  var player_id;
  if (data.active > 0) {
    // players
    if (first_show) {
      for (var i in data.players) {
        var p = data.players[i];

        var name=$('<span>'+p.name+'</span>');
        if (p.current) {
          name.addClass('this-player');
          player_id = i;
        }
        if (p.on_move) name.addClass('has-move');

        var lives='<span class="duck-lives">';
        for(j=0; j<p.lives; j++) lives+='<img src="assets/i/duckling'+p.color+'.png" alt="live">';
        lives+='</span>';

        $('#lives-row td:eq('+i+')').empty().append(name).append('<br>').append(lives);
      }
    } else {
      for (var i in data.players) {
        var p = data.players[i];
        var curr = $('.player:eq('+i+') img').length;
        if (p.current) {
          player_id = i;
        }
        for (var j=curr-1; j>=p.lives; j--)
          $('.player:eq('+i+') img:eq('+j+')').hide('slow', function() {$(this).detach();});

        if (p.on_move) $('.player:eq('+i+') span:eq(0)').addClass('has-move');
        else $('.player:eq('+i+') span:eq(0)').removeClass('has-move');
      }
    }

    var animation_targets=$(null).promise();
    if (!first_show) {
      animation_targets=process_targets(data.river);
    }

    var animation_draw=$(null).promise();

    if (first_show) {
      var p = data.pile;
      var card;

      if (p.id!==false) {
        card=$(resolve_card_contents('p_'+p.id)).attr('title', p.name+' - '+p.desc);
        $('#pile').empty().append(card);
      }

      $('#hand-inner').empty();
      for (var i in data.hand) {
        var p = data.hand[i];

        var card = $(resolve_card_contents('a_'+p.id)).attr('title', p.name+' - '+p.desc);

        $('#hand-inner').append(card);
      }
    } else { // only show newly drawn card
      // only if the card is missing
      if (has_played_card) {
        var p = data.hand[2];
        animation_draw=spawn_card('a_'+p.id, 'd0', 'h2', p.name+' - '+p.desc);
      }
    }

    first_show=false;
    has_played_card=false;

    // setup click handlers, but only after all animations are done
    return $.when(animation_draw, animation_targets).always(function() {
      $('#river').empty();
      for (var i in data.river) {
        var p = data.river[i];

        var card;
        switch (p.features) {
          case 0: // Duck::ONLY
            card=$(resolve_card_contents('d_'+p.color)).addClass('duck-only');
            break;
          case 1: // Duck::PROT
            card=$(resolve_card_contents('a_'+p.action_card)).addClass('duck-prot');
            card.append(resolve_card_contents('m_'+p.color));
            break;
          case 2: // Duck::DUCK
            card=$(resolve_card_contents('d_'+p.other_duck)).addClass('duck-duck');
            card.append(resolve_card_contents('m_'+p.color));
            break;
          case 3: // Duck::DUCK_PROT
            card=$(resolve_card_contents('d_'+p.color)).addClass('duck-duckprot');
            card.append(resolve_card_contents('m_'+p.other_duck));
            break;
        }

        if (p.target) card.append(resolve_card_contents('t_'+i));

        $('#river').append(card);
      }

      // show tooltips for imgs with title attribute
      $('div[title]').tooltip();

      if (data.players[player_id].on_move)
        add_click_handlers(data.moves);
      else
        cleanup_click_handlers();
    });
  } // active
  else { // inactive
    $('#gtitle').html(data.title);

    $('#tplayers').empty();
    for (var i in data.players) {
      if (!data.players.hasOwnProperty(i)) continue;
      var p=data.players[i];
      append_player(p);
    }

    return $(null).promise();
  }
}

function process_messages(data) {
  var slub=[];

  // check for null or non-array
  if (!data.messages || (data.messages.constructor !== Array)) return $(null).promise();

  for (var i in data.messages) {
    if (!data.messages.hasOwnProperty(i)) continue;
    var msg=data.messages[i];

    switch(msg.cmd) {
      case 100: // Text message
        $('#message-box').append('<div>'+msg.text+'</div>').animate({ scrollTop: $('#message-box')[0].scrollHeight}, 1000);
        break;
      case 101: // Action card play (player_id, card_id, river_pos, extras, died_pos)
        if (!msg.self) { // already performed at the initiator
          if (msg.card_id==10) // KACHNI_UNIK
            slub.push(spawn_card('a_'+msg.card_id, 'l'+msg.player_id, 'r'+msg.river_pos));
          else
            slub.push(spawn_card('a_'+msg.card_id, 'l'+msg.player_id, 'p0'));
        }

        switch (msg.card_id) {
          case 0: // DIVOKEJ_BILL
          case 2: // DVOJITA_TREFA
          case 9: // JEJDA_VEDLE
          case 15: // VYSTRELIT
            var dlen=msg.died_pos.length;
            for (var j=0; j<dlen; j++) {
              var duck_pos=msg.died_pos[j];
              slub.push(kill_duck(duck_pos, data.river[6-dlen+j].color));
            }
            break;

          case 3: // KACHNI_TANEC
            slub.push(return_all_ducks(data.river));
            break;

          case 4: // TURBOKACHNA
            if (msg.river_pos<6)
              slub.push(card_swap(msg.river_pos, 0));
            break;

          case 5: // ZIVY_STIT
            if (msg.river_pos<6)
              slub.push(card_hide(msg.river_pos, msg.extras, data.river[5].color));
            break;

          case 6: // ROSAMBO
            if (!msg.self) { // already performed at the initiator
              slub.push(card_shuffle(msg.extras));
            }
            break;

          case 11: // LEHARO
            if (msg.river_pos<6)
              slub.push(card_swap(msg.river_pos, msg.river_pos+1));
            break;

          case 12: // CHVATAM
            if (msg.river_pos<6)
              slub.push(card_swap(msg.river_pos, msg.river_pos-1));
            break;

          case 13: // KACHNI_POCHOD
            slub.push(return_first_duck(data.river[5].color));
            break;
        }
        break;

      case 102: // Unprotect ducks (positions)
        // wait for previous animations
        $.when.apply($, slub).always(function() {
          slub=[];
          for (var j=0; j<msg.positions.length; j++) {
            var unprotected;
            var card=data.river[msg.positions[j]];
            if (card.features==0) unprotect=$(resolve_card_contents('d_'+card.color)).addClass('duck-only');
            else if (card.features==2) unprotect=$(resolve_card_contents('d_'+card.other_duck)).addClass('duck-duck').append(resolve_card_contents('m_'+card.color));
            slub.push(spawn_card('a_10', 'r'+msg.positions[j], 'p0', '', unprotect));
          }
        });
        break;
    }
  }

  // return one composite promise for every promise in slub array
  return $.when.apply($, slub);
}

function append_player(p) {
  var line=$('<tr data-id="p_'+p.id+'"><td><b>'+p.name+'</b>&nbsp;&nbsp;</td></tr>');
  var tmp1=$('<td></td>');
  var sel=$('<select name="s_color" data-user="'+p.id+'" onchange="set_color(this)"></select>');
  var opt;

  if (!p.current) sel.prop('disabled', true);
  for (var k=-1; k<6; k++) {
    opt=$('<option value="'+k+'">'+colors[k]+'</option>');
    if (k == p.color) opt.prop('selected', true);
    sel.append(opt);
  }

  tmp1.append(sel);
  line.append(tmp1);
  $('#tplayers').append(line);

  show_hide_start_button();
}

function remove_player(id) {
  $('#tplayers tr[data-id="p_'+id+'"]').hide('slow', function() {
    $(this).detach();
    show_hide_start_button();
  });
}

function show_hide_start_button() {
  if ($('#tplayers tr').length > 1) {
    $('#bstart').show();
  } else {
    $('#bstart').hide();
  }
}

function cleanup_click_handlers() {
  $('#hand-inner div').removeClass('selected_move').off('click');
  $('#river div').removeClass('selected_move possible_move').off('click');
  $('#pile div').removeClass('possible_move').off('click');
}

function add_click_handlers(data) {
  cleanup_click_handlers();

  $('#hand-inner div').click(function(e){
    // zhasneme predoslu vybratu a zasvietime novu
    $('#hand-inner div').removeClass('selected_move');
    var current_card=$(e.currentTarget);
    current_card.addClass('selected_move');

    // cistenie vysvietenych kariet na stole
    $('#river div').removeClass('possible_move selected_move').off('click');
    $('#pile div').removeClass('possible_move').off('click');

    // ukoncime sortovanie v pripade rosambo
    if (sorting_rosambo) {
      sorting_rosambo = false;
      $('#river').sortable('destroy');
      $('#river').html(rosambo_river_backup);
    }

    var first_click=data[0][current_card.index()];
    var second_click=data[1][current_card.index()];

    for (var i=0; i<6; i++) {
      if (second_click === false) { // 1-klikove karty
        // vysvietit a zaroven pridat onClick handler
        if (first_click[i]) $('#river div:eq('+i+')').addClass('possible_move').click(function(e){
          card_play_click(current_card.index(), $(this).index(), null, '');
        });
      } else if (second_click === true) { // 6-klikova karta ROSAMBO
        if (first_click[i]) $('#river div:eq('+i+')').addClass('possible_move').click(function(e){
          // cistenie vysvietenych kariet na stole
          $('#river div').removeClass('possible_move selected_move').off('click');
          $('#pile div').removeClass('possible_move').off('click');

          rosambo_play(current_card.index());
        });
      } else { // 2-klikove karty ZIVY_STIT, JEJDA_VEDLE
        if (first_click[i]) $('#river div:eq('+i+')').addClass('possible_move').click(function(e){
          card_select($(this), second_click[$(this).index()], current_card.index());
        });
      }
    }
    if (first_click[6]) $('#pile div').addClass('possible_move').click(function(e){
      card_play_click(current_card.index(), 6, null, '');
    });
  });
}

// change the game view
function switch_game_phase(phase) {
  $('[data-phase]').hide();
  $('[data-phase="'+phase+'"]').show();
}

function ws_connect() {
  $('#conn-alert').slideUp();
  var timeout = setTimeout(function() {
    $('#conn-alert').slideDown();
  }, 2000);

  window.ws = new WebSocket(window.ws_uri);
  ws.onmessage = function(msg) {
    data_ready(JSON.parse(msg.data));
  };

  ws.onclose = function() {
    $('#conn-alert').slideDown();
    cleanup_click_handlers();
    window.gid = 0;
    switch_game_phase('noGame');
    window.history.pushState(null, '', '?');
    $('#btNew').hide();
  };

  ws.onopen = function() {
    clearTimeout(timeout);
    $('#conn-alert').slideUp();
    $('#btNew').show();

    ws.exec('authenticate', {
      gameId: gid
    });
    if (gid == 0) {
      switch_game_phase('noGame');
      ws.exec('gameList');
    } else {
      switch_game_phase('beforeGame');
      ws.exec('gameDetails');
      ws.exec('chatLoad');
    }
  };
}

function gui_start(gid, ws_uri) {
  window.gid = gid;
  window.ws_uri = ws_uri;

  WebSocket.prototype.exec = function(cmd, args) {
    if (this.readyState != 1) return;
    var data = {cmd: cmd};

    if (args !== undefined) {
      data.args = args;
    }

    this.send(JSON.stringify(data));
  };

  ws_connect();

  // scroll to the bottom
  $('#message-box').scrollTop($('#message-box')[0].scrollHeight);

  // send text message to others
  $('#message-input').keydown(function(event) {
    if (event.which == 13) {
      event.preventDefault();
      var msg = $('#message-input').val();
      if (msg == '') return;

      ws.exec('chat', {text: msg});
      $('#message-input').val('');
    }
  });
}