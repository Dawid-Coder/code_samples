//=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-//
#pragma compress 1
//=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-//

#define ELO_CLANS_INTEGRATION     // Odkomentuj jeśli masz wgrany na serwer plugin 'SS_ELO_CLANS_SYSTEM' i chcesz go zintegrować z tym pluginem
#define ELO_RESET_INTEGRATION     // Odkomentuj jeśli masz wgrany na serwer plugin 'SS_ELO_RESET_SYSTEM' i chcesz go zintegrować z tym pluginem

#include <amxmodx>
#include <amxmisc>
#include <colorchat>
#include <sqlx>
#include <csx>
#include <fun>
#include <fakemeta>
#if defined ELO_RESET_INTEGRATION
#include <ss_elo_reset_system>
#endif


#define PLUGIN "ELO - RANK SYSTEM"
#define VERSION "v3"
#define AUTHOR "Smiguel"

#define TASK_RANGA 2001


// Zmienne ogólne
new bool: ma_vipa[33];
new komunikaty[33];
new hud[33];
new prefix[33];
new strona[33];
new flag_vip[16];
new mvp_punkty[33];
new punkty_elo[33];
new rank_maxplayers,
	rank_position[33];
new ilosc_zabojstw[33],
	ilosc_zabojstw_hs[33],
	ilosc_zgonow[33],
	ilosc_mvp[33],
	ilosc_asyst[33];
new zadane_obrazenia[33][33];
new msg_scoreinfo;
new ilosc_rang,
	ranga[33],
	nazwa_rangi[33][33],
	prog_rangi[33];
	

// Zmienne od cvarów
new cv_hostname,
	cv_username,
	cv_password,
	cv_database;
new cv_minplayers,
	cv_points_kill,
	cv_points_kill_hs,
	cv_points_bomb_planted,
	cv_points_bomb_defused,
	cv_points_assist,
	cv_points_mvp,
	cv_points_win_tt,
	cv_points_win_ct;
new cv_minus_points_for_death;
new cv_flag_vip,
	cv_bonus_points_vip;
new cv_chat_prefix;
new cv_assist_min_damage;
new cv_hud_system_active,
	cv_hud_x_axis,
	cv_hud_y_axis,
	cv_hud_r,
	cv_hud_g,
	cv_hud_b,
	cv_hud_site;
new cv_mvp_mode,
	cv_mvp_points_kill,
	cv_mvp_points_kill_hs,
	cv_mvp_points_bomb_planted,
	cv_mvp_points_bomb_defused,
	cv_mvp_points_assist,
	cv_mvp_reset_points_for_death;
	
// Zmiene od plików
new configs_dir[64];
new plik_rangi[96];
new linia_z_pliku[128], dlugosc, dane[2][33];

// Zmienne od bazy daych
new hostname[33], username[33], password[33], database[33];
new Handle: mysql_baza;
new err, error[33];


//=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-//
// POCZĄTKOWA CZĘŚĆ PLUGINU
//=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-//
public mysql_init()
{
	mysql_baza = SQL_MakeDbTuple(hostname, username, password, database);

	new Handle: connection = SQL_Connect(mysql_baza, err, error,charsmax(error));
	new Handle: query;
	
	query = SQL_PrepareQuery(connection,
	"CREATE TABLE IF NOT EXISTS `elo_rank_system` ( \
		`id` int NOT NULL AUTO_INCREMENT, \
		`nick` varchar(33) NOT NULL, \
		`elo_points` int NOT NULL, \
		`kills` int NOT NULL, \
		`kills_hs` int NOT NULL, \
		`deaths` int NOT NULL, \
		`assists` int NOT NULL, \
		`mvp` int NOT NULL, \
		`komunikaty` tinyint NOT NULL, \
		`hud` tinyint NOT NULL, \
		PRIMARY KEY (`id`) \
	);");
	SQL_Execute(query);
	
	SQL_FreeHandle(query);
	SQL_FreeHandle(connection);
}
public Query(failstate, Handle:query, error[])
{
	if(failstate != TQUERY_SUCCESS)
		return;
}

public plugin_init()
{	
	register_plugin(PLUGIN, VERSION, AUTHOR);
	
	
	register_clcmd("say /rank", "menu_glowne");
	register_clcmd("say /top15", "menu_glowne");
	register_clcmd("say /elo", "menu_glowne");
	register_clcmd("say_team /rank", "menu_glowne");
	register_clcmd("say_team /top15", "menu_glowne");
	register_clcmd("say_team /elo", "menu_glowne");
	register_clcmd("elo_menu", "menu_glowne");
	
	
	register_event("SendAudio", "wygrana_tt", "a", "2&%!MRAD_terwin");
	register_event("SendAudio", "wygrana_ct", "a", "2&%!MRAD_ctwin");
	register_event("DeathMsg", "zabojstwo", "a");
	register_event("Damage", "obrazenia", "be", "2!0", "3=0", "4!0");
	register_logevent("koniec_rundy", 2, "1=Round_End");
	register_logevent("poczatek_rundy", 2, "1=Round_Start");
	
	
	msg_scoreinfo = get_user_msgid("ScoreInfo");
	
	
	cv_minplayers                 = register_cvar("ss_elo_minplayers",              "2");              // Od ilu graczy ma działać system

	cv_points_kill                = register_cvar("ss_elo_points_kill",             "4");              // Ile punktów za zabójstwo
	cv_points_kill_hs             = register_cvar("ss_elo_points_kill_hs",          "6");              // Ile punktów za zabójstwo w głowę
	cv_points_bomb_planted        = register_cvar("ss_elo_bomb_planted",            "8");              // Ile punktów za podłożenie bomby
	cv_points_bomb_defused        = register_cvar("ss_elo_bomb_defused",            "8");              // Ile punktów za rozbrojenie bomby
	cv_points_assist              = register_cvar("ss_elo_points_assist",           "2");              // Ile punktów za asystę
	cv_points_mvp                 = register_cvar("ss_elo_points_mvp",              "10");             // Ile punktów za mvp (najlepszego gracza rundy)
	cv_points_win_tt              = register_cvar("ss_elo_points_win_tt",           "3");              // Ile punktów dla drużyny TT za wygraną rundę
	cv_points_win_ct              = register_cvar("ss_elo_points_win_ct",           "3");              // Ile punktów dla drużyny CT za wygraną rundę

	cv_minus_points_for_death     = register_cvar("ss_elo_minus_points_for_death",  "10");             // Ile ujemnych punktów za zgon

	cv_flag_vip                   = register_cvar("ss_elo_flag_vip",                "v");              // Flaga vipa
	cv_bonus_points_vip           = register_cvar("ss_elo_bonus_points_vip",        "2");              // Ile dodatkowych puktów dla vipa

	cv_chat_prefix                = register_cvar("ss_elo_chat_prefix",             "RANK");           // Prefix na czacie

	cv_assist_min_damage          = register_cvar("ss_elo_assist_min_damage",       "40");             // Minimalne obrażenia, aby dostać asystę

	cv_hud_system_active          = register_cvar("ss_elo_hud_system_active",       "1");              // Czy wbudowany system HUD ma być aktywny? (1 - TAK | 0 - NIE)
	cv_hud_x_axis                 = register_cvar("ss_elo_hud_x_axis",              "2");              // Odległość HUD od lewej ściany monitora (WARTOŚCI: 0-100, lub -1 - HUD będzie na środku ekranu względem górnej ściany monitora)
	cv_hud_y_axis                 = register_cvar("ss_elo_hud_y_axis",              "16");             // Odległość HUD od górnej ściany monitora (WARTOŚCI: 0-100, lub -1 - HUD będzie na środku ekranu względem lewej ściany monitora)
	cv_hud_r                      = register_cvar("ss_elo_hud_r",                   "0");              // Nasycenie koloru czerwonego (WARTOŚCI: 0-255)
	cv_hud_g                      = register_cvar("ss_elo_hud_g",                   "255");            // Nasycenie koloru zielonego (WARTOŚCI: 0-255)
	cv_hud_b                      = register_cvar("ss_elo_hud_b",                   "0");              // Nasycenie koloru niebieskiego (WARTOŚCI: 0-255)

	cv_hud_site                   = register_cvar("ss_elo_hud_site",                "TwojaStrona.pl"); // Nasycenie koloru niebieskiego (WARTOŚCI: 0-255)

	cv_mvp_mode                   = register_cvar("ss_elo_mvp_mode",                "1");              // Jak ma działać system MVP? (1 - MVP dostaje najlepszy gracz | 2 - MVP dostaje najlepszy gracz drużyny, która wygrała rundę)
	cv_mvp_points_kill            = register_cvar("ss_elo_mvp_points_kill",         "2");              // Ile punktów MVP za zabójstwo
	cv_mvp_points_kill_hs         = register_cvar("ss_elo_mvp_points_kill_hs",      "3");              // Ile punktów MVP za zabójstwo w głowę
	cv_mvp_points_bomb_planted    = register_cvar("ss_elo_mvp_points_bomb_planted", "3");              // Ile punktów MVP za podłożenie bomby
	cv_mvp_points_bomb_defused    = register_cvar("ss_elo_mvp_points_bomb_defused", "3");              // Ile punktów MVP za rozbrojenie bomby
	cv_mvp_points_assist          = register_cvar("ss_elo_mvp_points_assist",       "1");              // Ile punktów MVP za asystę
	cv_mvp_reset_points_for_death = register_cvar("ss_elo_mvp_reset_points_death",  "0");              // Czy martwy gracz może dostać MVP? (1 - TAK | 0 - NIE)
	
	
	cv_hostname                   = register_cvar("ss_elo_rank_system_hostname", "hostname");
	cv_username                   = register_cvar("ss_elo_rank_system_username", "username");
	cv_password                   = register_cvar("ss_elo_rank_system_password", "password");
	cv_database                   = register_cvar("ss_elo_rank_system_database", "database");
	
	get_configsdir(configs_dir, charsmax(configs_dir));
	server_cmd("exec %s/sql.cfg", configs_dir);
	server_exec();
	
	server_cmd("exec %s/amxx.cfg", configs_dir);
	server_exec();
	
	get_pcvar_string(cv_hostname, hostname,charsmax(hostname));
	get_pcvar_string(cv_username, username,charsmax(username));
	get_pcvar_string(cv_password, password,charsmax(password));
	get_pcvar_string(cv_database, database,charsmax(database));
	
	set_pcvar_string(cv_hostname, "PROTECTED");
	set_pcvar_string(cv_username, "PROTECTED");
	set_pcvar_string(cv_password, "PROTECTED");
	set_pcvar_string(cv_database, "PROTECTED");
	
	get_pcvar_string(cv_flag_vip, flag_vip,charsmax(flag_vip));
	get_pcvar_string(cv_chat_prefix, prefix,charsmax(prefix));
	get_pcvar_string(cv_hud_site, strona,charsmax(strona));
	
	
	set_task(1.0, "mysql_init");
	set_task(1.0, "configs_init");
}

public configs_init()
{
	get_configsdir(configs_dir, charsmax(configs_dir));
	
	formatex(plik_rangi,charsmax(plik_rangi), "%s/SS/ELO_SYSTEM/rangi.ini", configs_dir);
	
	if(file_exists(plik_rangi))
	{
		for(new i = 0, n = 1; read_file(plik_rangi, i, linia_z_pliku,charsmax(linia_z_pliku), dlugosc); i++)
		{
			if(containi(linia_z_pliku, "//") == -1 && containi(linia_z_pliku, ";") == -1 && !equal(linia_z_pliku, "", 1))
			{
				parse(linia_z_pliku, dane[0],charsmax(dane[]), dane[1],charsmax(dane[]));
				
				formatex(nazwa_rangi[n],charsmax(nazwa_rangi[]), "%s", dane[0]);
				prog_rangi[n] = str_to_num(dane[1]);
				ilosc_rang++;
				
				n++;
			}
			
		}
	}
}

public plugin_natives()
{
	register_library("ss_elo_rank_system");
	
	register_native("ss_get_user_elo_points", "_ss_get_user_elo_points");
	register_native("ss_set_user_elo_points", "_ss_set_user_elo_points");
	register_native("ss_get_user_elo_rank", "_ss_get_user_elo_rank");
	register_native("ss_get_srednia_ranga", "_ss_get_srednia_ranga");
}

public _ss_get_user_elo_points(plugin, params) // (id)
{
	new id = get_param(1);
	return punkty_elo[id];
}

public _ss_set_user_elo_points(plugin, params) // (id, wartosc)
{
	new id = get_param(1);
	new wartosc = get_param(2);
	
	punkty_elo[id] = wartosc;
}

public _ss_get_user_elo_rank(plugin, params) // (id, tablica)
{
	new id = get_param(1);
	set_string(2, nazwa_rangi[ranga[id]], charsmax(nazwa_rangi[]));
}

public _ss_get_srednia_ranga(plugin, params) // (punkty_elo, tablica)
{
	new param_punkty_elo = get_param(1);
	new param_ranga = 0
	
	for(new i = 1; i <= ilosc_rang; i++)
	{
		if(param_punkty_elo >= prog_rangi[i] && param_ranga < ilosc_rang)
		{
			param_ranga++;
		}
	}
	
	set_string(2, nazwa_rangi[param_ranga], charsmax(nazwa_rangi[]));
}

public client_authorized(id)
{
	if(is_user_hltv(id))
		return PLUGIN_CONTINUE;
		
	if(get_user_flags(id) & read_flags(flag_vip))
		ma_vipa[id] = true;
	else 
		ma_vipa[id] = false;
		
	if(task_exists(id + TASK_RANGA))
		remove_task(id + TASK_RANGA);
		
	if(get_pcvar_num(cv_hud_system_active) == 1)
		hud[id] = true;
		
	komunikaty[id] = true;
	punkty_elo[id] = 1000;
	ranga[id] = 1;
	ilosc_zabojstw[id] = 0;
	ilosc_zabojstw_hs[id] = 0;
	ilosc_zgonow[id] = 0;
	ilosc_mvp[id] = 0;
	ilosc_asyst[id] = 0;
	mvp_punkty[id] = 0;
	
	wczytaj_dane(id);
	
	return PLUGIN_CONTINUE;
}

public client_disconnected(id)
{
	if(is_user_hltv(id))
		return PLUGIN_CONTINUE;
		
	mvp_punkty[id] = 0;
	
	zapisz_dane(id);
	
	return PLUGIN_CONTINUE;
}

//=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-//
// GŁÓWNA CZĘŚĆ PLUGINU
//=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-//
public poczatek_rundy()
{
	if(get_playersnum(0) < get_pcvar_num(cv_minplayers))
		return PLUGIN_CONTINUE;
		
	for(new id = 1; id <= 32; id++)
	{
		if(is_user_connected(id))
		{
			mvp_punkty[id] = 0;
		}
	}
	
	return PLUGIN_CONTINUE;
}

public koniec_rundy()
{
	if(get_playersnum(0) < get_pcvar_num(cv_minplayers))
		return PLUGIN_CONTINUE;
		
	new double, id;
	
	for(new i = 1; i <= 32; i++)
	{
		if(mvp_punkty[id] == mvp_punkty[i])
		{
			double = 1;
		}
		else if(mvp_punkty[id] < mvp_punkty[i])
		{
			id = i;	
			double = 0;
		}
	}
	
	if(!double && id)
	{
		if(mvp_punkty[id] > 0)
		{
			new nick[33];
			get_user_name(id, nick, charsmax(nick));
			
			if(ma_vipa[id])
				punkty_elo[id] += get_pcvar_num(cv_points_mvp) + get_pcvar_num(cv_bonus_points_vip);
			else
				punkty_elo[id] += get_pcvar_num(cv_points_mvp);
				
			ilosc_mvp[id]++;
			
			for(new i = 1; i <= 32; i++)
			{
				if(is_user_connected(i) && komunikaty[i])
				{
					ColorChat(i, GREEN, "^x04[%s]^x03 %s^x04 (^x03%d^x04)^x01 otrzymal(a)^x04 %d %s^x01 za zdobycie MVP", prefix, nick, punkty_elo[id], ma_vipa[id] ? (get_pcvar_num(cv_points_mvp) + get_pcvar_num(cv_bonus_points_vip)) : get_pcvar_num(cv_points_mvp), odmiana(get_pcvar_num(cv_points_mvp), "punkt", "ow", "", "y"));
				}
			}
		}
	}
	
	for(new id = 1; id <= 32; id++)
	{
		if(is_user_connected(id))
			aktualizuj_rank(id);
	}
	
	return PLUGIN_CONTINUE;
}

public obrazenia(victim)
{
	if(!is_user_alive(victim) || get_playersnum(0) < get_pcvar_num(cv_minplayers))
		return PLUGIN_CONTINUE;

	new damage = read_data(2);
	new attacker = get_user_attacker(victim);
	
	if(!is_user_alive(attacker))
		return PLUGIN_CONTINUE;

	zadane_obrazenia[attacker][victim] += damage;

	return PLUGIN_CONTINUE;
}

public zabojstwo()
{
	new kid = read_data(1); // zabojca
	new vid = read_data(2); // ofiara
	new hs = read_data(3);  // headshot
	
	if(get_user_team(kid) == get_user_team(vid))
		return PLUGIN_CONTINUE;
		
	new kid2 = 0;
	new damage2 = 0;

	for(new id = 1; id <= 32; id++)
	{
		if(id != kid && is_user_connected(id) && get_user_team(kid) == get_user_team(id) && zadane_obrazenia[id][vid] >= get_pcvar_num(cv_assist_min_damage) && zadane_obrazenia[id][vid] > damage2)
		{
			kid2 = id;
			damage2 = zadane_obrazenia[id][vid];
		}

		zadane_obrazenia[id][vid] = 0;
	}

	if(kid2 > 0 && damage2 > get_pcvar_num(cv_assist_min_damage))
	{
		set_user_frags(kid2, get_user_frags(kid2) + 1);

		message_begin(MSG_ALL, msg_scoreinfo);
		write_byte(kid2);
		write_short(get_user_frags(kid2));
		write_short(get_user_deaths(kid2));
		write_short(0);
		write_short(get_user_team(kid));
		message_end();
			
		new nick_atakujacego[33], nick_asystujacego[33], nick_ofiary[33];
		get_user_name(kid, nick_atakujacego, charsmax(nick_atakujacego))
		get_user_name(kid2, nick_asystujacego, charsmax(nick_asystujacego))
 		get_user_name(vid, nick_ofiary, charsmax(nick_ofiary))
			
		if(ma_vipa[kid2])
			punkty_elo[kid2] += get_pcvar_num(cv_points_assist) + get_pcvar_num(cv_bonus_points_vip);
		else
			punkty_elo[kid2] += get_pcvar_num(cv_points_assist);
				
		ilosc_asyst[kid2]++;
		mvp_punkty[kid2] += get_pcvar_num(cv_mvp_points_assist);
		
		if(komunikaty[kid2])
		{
			ColorChat(kid2, GREEN, "^x04[%s]^x03 %s^x04 (^x03%d^x04)^x01 otrzymales(as)^x04 %d %s^x01 za pomoc^x03 %s^x04 (^x03%d^x04)^x01 w zabiciu^x03 %s^x04 (^x03%d^x04)", prefix, nick_asystujacego, punkty_elo[kid2], ma_vipa[kid2] ? (get_pcvar_num(cv_points_assist) + get_pcvar_num(cv_bonus_points_vip)) : get_pcvar_num(cv_points_assist), odmiana(get_pcvar_num(cv_points_assist), "punkt", "ow", "", "y"), nick_atakujacego, punkty_elo[kid], nick_ofiary, punkty_elo[vid]);
		}
	}
		
		
	new nick[33], nick_ofiary[33];
	get_user_name(kid, nick, charsmax(nick));
	get_user_name(vid, nick_ofiary, charsmax(nick_ofiary));
		
	if(hs)
	{		
		if(ma_vipa[kid])
			punkty_elo[kid] += get_pcvar_num(cv_points_kill_hs) + get_pcvar_num(cv_bonus_points_vip);
		else
			punkty_elo[kid] += get_pcvar_num(cv_points_kill_hs);
			
		ilosc_zabojstw_hs[kid]++;
		ilosc_zabojstw[kid]++;
		mvp_punkty[kid] += get_pcvar_num(cv_mvp_points_kill_hs);
		
		if(komunikaty[kid])
		{
			ColorChat(kid, GREEN, "^x04[%s]^x03 %s^x04 (^x03%d^x04)^x01 otrzymales(as)^x04 %d %s^x01 za zabicie w glowe^x03 %s^x04 (^x03%d^x04)", prefix, nick, punkty_elo[kid], ma_vipa[kid] ? (get_pcvar_num(cv_points_kill_hs) + get_pcvar_num(cv_bonus_points_vip)) : get_pcvar_num(cv_points_kill_hs), odmiana(get_pcvar_num(cv_points_kill_hs), "punkt", "ow", "", "y"), nick_ofiary, punkty_elo[vid]);
		}
	}
	else
	{
		if(ma_vipa[kid])
			punkty_elo[kid] += get_pcvar_num(cv_points_kill) + get_pcvar_num(cv_bonus_points_vip);
		else
			punkty_elo[kid] += get_pcvar_num(cv_points_kill);
			
		ilosc_zabojstw[kid]++;
		mvp_punkty[kid] += get_pcvar_num(cv_mvp_points_kill);
		
		if(komunikaty[kid])
		{
			ColorChat(kid, GREEN, "^x04[%s]^x03 %s^x04 (^x03%d^x04)^x01 otrzymales(as)^x04 %d %s^x01 za zabicie^x03 %s^x04 (^x03%d^x04)", prefix, nick, punkty_elo[kid], ma_vipa[kid] ? (get_pcvar_num(cv_points_kill) + get_pcvar_num(cv_bonus_points_vip)) : get_pcvar_num(cv_points_kill), odmiana(get_pcvar_num(cv_points_kill), "punkt", "ow", "", "y"), nick_ofiary, punkty_elo[vid]);
		}
	}
	
	if(get_pcvar_num(cv_mvp_reset_points_for_death))
		mvp_punkty[vid] = 0;
	
	punkty_elo[vid] -= get_pcvar_num(cv_minus_points_for_death);
	ilosc_zgonow[vid]++;
	
	if(komunikaty[vid])
	{
		if(kid == vid || kid == 0)
		{
			ColorChat(vid, GREEN, "^x04[%s]^x03 %s^x04 (^x03%d^x04)^x01 straciles(as)^x04 %d %s^x01 za zgon", prefix, nick_ofiary, punkty_elo[vid], get_pcvar_num(cv_minus_points_for_death), odmiana(get_pcvar_num(cv_minus_points_for_death), "punkt", "ow", "", "y"));
		}
		else
		{
			ColorChat(vid, GREEN, "^x04[%s]^x03 %s^x04 (^x03%d^x04)^x01 straciles(as)^x04 %d %s^x01 za smierc z reki^x03 %s^x04 (^x03%d^x04)", prefix, nick_ofiary, punkty_elo[vid], get_pcvar_num(cv_minus_points_for_death), odmiana(get_pcvar_num(cv_minus_points_for_death), "punkt", "ow", "", "y"), nick, punkty_elo[kid]);
		}
	}
		
	return PLUGIN_CONTINUE;
}

public bomb_planted(id)
{
	if(get_playersnum(0) < get_pcvar_num(cv_minplayers))
		return PLUGIN_CONTINUE;
		
	new nick[33];
	get_user_name(id, nick, charsmax(nick));
	
	if(ma_vipa[id])
		punkty_elo[id] += get_pcvar_num(cv_points_bomb_planted) + get_pcvar_num(cv_bonus_points_vip);
	else
		punkty_elo[id] += get_pcvar_num(cv_points_bomb_planted);
				
	mvp_punkty[id] += get_pcvar_num(cv_mvp_points_bomb_planted);
			
	for(new i = 1; i <= 32; i++)
	{
		if(is_user_connected(i) && get_user_team(i) == get_user_team(id))
		{			
			if(komunikaty[i])
			{
				ColorChat(i, GREEN, "^x04[%s]^x03 %s^x04 (^x03%d^x04)^x01 otrzymal(a)^x04 %d %s^x01 za podlozenie bomby", prefix, nick, punkty_elo[id], ma_vipa[id] ? (get_pcvar_num(cv_points_bomb_planted) + get_pcvar_num(cv_bonus_points_vip)) : get_pcvar_num(cv_points_bomb_planted), odmiana(get_pcvar_num(cv_points_bomb_planted), "punkt", "ow", "", "y"));
			}
		}
	}
	
	return PLUGIN_CONTINUE;
}

public bomb_defused(id)
{
	if(get_playersnum(0) < get_pcvar_num(cv_minplayers))
		return PLUGIN_CONTINUE;
		
	new nick[33];
	get_user_name(id, nick, charsmax(nick));
	
	if(ma_vipa[id])
		punkty_elo[id] += get_pcvar_num(cv_points_bomb_defused) + get_pcvar_num(cv_bonus_points_vip);
	else
		punkty_elo[id] += get_pcvar_num(cv_points_bomb_defused);
		
	mvp_punkty[id] += get_pcvar_num(cv_mvp_points_bomb_defused);
			
	for(new i = 1; i <= 32; i++)
	{
		if(is_user_connected(i) && get_user_team(i) == get_user_team(id))
		{		
			if(komunikaty[i])
			{
				ColorChat(i, GREEN, "^x04[%s]^x03 %s^x04 (^x03%d^x04)^x01 otrzymal(a)^x04 %d %s^x01 za rozbrojenie bomby", prefix, nick, punkty_elo[id], ma_vipa[id] ? (get_pcvar_num(cv_points_bomb_defused) + get_pcvar_num(cv_bonus_points_vip)) : get_pcvar_num(cv_points_bomb_defused), odmiana(get_pcvar_num(cv_points_bomb_defused), "punkt", "ow", "", "y"));
			}
		}
	}
	
	return PLUGIN_CONTINUE;
}

public wygrana_tt()
{
	if(get_playersnum(0) < get_pcvar_num(cv_minplayers))
		return PLUGIN_CONTINUE;
		
	if(get_pcvar_num(cv_mvp_mode) == 2)
	{
		for(new id = 1; id <= 32; id++)
		{
			if(is_user_connected(id) && get_user_team(id) == 2)
			{
				mvp_punkty[id] = 0;
			}
		}
	}
	
	for(new i = 1; i <= 32; i++)
	{
		if(is_user_connected(i) && get_user_team(i) == 1)
		{
			punkty_elo[i] += get_pcvar_num(cv_points_win_tt);
			
			if(komunikaty[i])
			{
				ColorChat(i, GREEN, "^x04[%s]^x03 Druzyna TT^x01 otrzymala po^x04 %d %s^x01 za zwyciestwo rundy", prefix, get_pcvar_num(cv_points_win_tt), odmiana(get_pcvar_num(cv_points_win_tt), "punkt", "ow", "", "y"));
			}
		}
		else if(is_user_connected(i))
		{
			if(komunikaty[i])
			{
				ColorChat(i, GREEN, "^x04[%s]^x03 Druzyna TT^x01 otrzymala po^x04 %d %s^x01 za zwyciestwo rundy", prefix, get_pcvar_num(cv_points_win_tt), odmiana(get_pcvar_num(cv_points_win_tt), "punkt", "ow", "", "y"));
			}
		}
	}
	
	return PLUGIN_CONTINUE;
}

public wygrana_ct()
{
	if(get_playersnum(0) < get_pcvar_num(cv_minplayers))
		return PLUGIN_CONTINUE;
		
	if(get_pcvar_num(cv_mvp_mode) == 2)
	{
		for(new id = 1; id <= 32; id++)
		{
			if(is_user_connected(id) && get_user_team(id) == 1)
			{
				mvp_punkty[id] = 0;
			}
		}
	}
	
	for(new i = 1; i <= 32; i++)
	{
		if(is_user_connected(i) && get_user_team(i) == 2)
		{
			punkty_elo[i] += get_pcvar_num(cv_points_win_ct);
			
			if(komunikaty[i])
			{
				ColorChat(i, GREEN, "^x04[%s]^x03 Druzyna CT^x01 otrzymala po^x04 %d %s^x01 za zwyciestwo rundy", prefix, get_pcvar_num(cv_points_win_ct), odmiana(get_pcvar_num(cv_points_win_ct), "punkt", "ow", "", "y"));
			}
		}
		else if(is_user_connected(i))
		{
			if(komunikaty[i])
			{
				ColorChat(i, GREEN, "^x04[%s]^x03 Druzyna CT^x01 otrzymala po^x04 %d %s^x01 za zwyciestwo rundy", prefix, get_pcvar_num(cv_points_win_ct), odmiana(get_pcvar_num(cv_points_win_ct), "punkt", "ow", "", "y"));
			}
		}
	}
	
	return PLUGIN_CONTINUE;
}

public nadaj_range(id)
{
	id -= TASK_RANGA;
		
	if(punkty_elo[id] >= prog_rangi[ranga[id] + 1])
	{
		ranga[id]++;
	}
	else if(punkty_elo[id] < prog_rangi[ranga[id]])
	{
		ranga[id]--;
	}
	
	if(punkty_elo[id] < 0)
		punkty_elo[id] = 0;
		
	
	if(hud[id])
	{
		if(get_pcvar_num(cv_hud_system_active) == 1)
		{
			new nick[33], r, g, b, x[6], y[6];
			
			if(get_pcvar_num(cv_hud_r) > 255 || get_pcvar_num(cv_hud_r) < 0)
				r = 255;
			else r = get_pcvar_num(cv_hud_r);
			
			if(get_pcvar_num(cv_hud_g) > 255 || get_pcvar_num(cv_hud_g) < 0)
				g = 255;
			else g = get_pcvar_num(cv_hud_g);
			
			if(get_pcvar_num(cv_hud_b) > 255 || get_pcvar_num(cv_hud_b) < 0)
				b = 255;
			else b = get_pcvar_num(cv_hud_b);
			
			if(get_pcvar_num(cv_hud_x_axis) == -1)
				formatex(x,charsmax(x), "-1.0");
			else if(get_pcvar_num(cv_hud_x_axis) > 99 || get_pcvar_num(cv_hud_x_axis) < 0)
				formatex(x,charsmax(x), "0.0");
			else
				formatex(x,charsmax(x), "0.%02d", get_pcvar_num(cv_hud_x_axis));
				
			if(get_pcvar_num(cv_hud_y_axis) == -1)
				formatex(y,charsmax(y), "-1.0");
			else if(get_pcvar_num(cv_hud_y_axis) > 99 || get_pcvar_num(cv_hud_y_axis) < 0)
				formatex(y,charsmax(y), "0.0");
			else
				formatex(y,charsmax(y), "0.%02d", get_pcvar_num(cv_hud_y_axis));
			
			if(!is_user_alive(id))
			{
				new target = pev(id, pev_iuser2);
				
				if(is_user_connected(target) && is_user_alive(target))
				{
					get_user_name(target, nick,charsmax(nick));
					
					set_hudmessage(r, g, b, str_to_float(x), str_to_float(y), 0, 6.00, 0.92, 0.10, 0.10, -1);
					show_hudmessage(id, "--==| %s |==--^n[Nick: %s]^n[Skill level: %s (%d)]^n[Konto: %s]^n[Rank: %d/%d]", strona, nick, nazwa_rangi[ranga[target]], punkty_elo[target], ma_vipa[target] ? "VIP" : "ZWYKLE", rank_position[target], rank_maxplayers);
				}
			}
			else
			{
				get_user_name(id, nick,charsmax(nick));
					
				set_hudmessage(r, g, b, str_to_float(x), str_to_float(y), 0, 6.00, 0.92, 0.10, 0.10, -1);
				show_hudmessage(id, "--==| %s |==--^n[Nick: %s]^n[Skill level: %s (%d)]^n[Konto: %s]^n[Rank: %d/%d]", strona, nick, nazwa_rangi[ranga[id]], punkty_elo[id], ma_vipa[id] ? "VIP" : "ZWYKLE", rank_position[id], rank_maxplayers);
			}
		}
	}
}

public menu_glowne(id)
{
	new item[3][128];
	new menu = menu_create("\w--\y==\r| \wELO RANK SYSTEM \r|\y==\w--\R", "menu_glowne_handle");
	
#if defined ELO_RESET_INTEGRATION
	if(ss_elors_get_user_winner(id) == 1)
	{
		formatex(item[0],charsmax(item[]), "\rGratulacje! \yZostales graczem miesiaca :)^n%s^n", (ss_elors_get_cvar_skiny() == 1 || ss_elors_get_cvar_skiny() == 3) ? ((ss_elors_get_cvar_poswiata() == 1 || ss_elors_get_cvar_poswiata() == 3) ? "\wNagroda: \rskiny + poswiata" : "\wNagroda: \rskiny") : (ss_elors_get_cvar_poswiata() == 1 || ss_elors_get_cvar_poswiata() == 3) ? "\wNagroda: \rposwiata" : "\yOby tak dalej!");
		menu_additem(menu, item[0], "0");
	}
#endif
		
	menu_additem(menu, "\yRank", "1");
	menu_additem(menu, "\yTop 15", "2");
	
#if defined ELO_CLANS_INTEGRATION
	menu_additem(menu, "\yDruzyny^n", "3");
#endif
		
	menu_additem(menu, "\yLista rang^n", "4");
	
#if defined ELO_RESET_INTEGRATION
	menu_additem(menu, "\yNajlepsi gracze ubieglych lat^n", "5");
#endif
	
	formatex(item[0],charsmax(item[]), "\yKomunikaty \d[%s\d]", komunikaty[id] == 1 ? "\wON" : "\rOFF");
	formatex(item[1],charsmax(item[]), "\yHUD \d[%s\d]", hud[id] == 1 ? "\wON" : "\rOFF");
	
#if defined ELO_RESET_INTEGRATION
	if((ss_elors_get_cvar_skiny() == 1 || ss_elors_get_cvar_skiny() == 3) && ss_elors_get_user_winner(id) == 1)
		formatex(item[2],charsmax(item[]), "\ySkiny \d[%s\d]", ss_elors_get_onoff_models(id) == 1 ? "\wON" : "\rOFF");
#endif
		
	menu_additem(menu, item[0], "6");
	menu_additem(menu, item[1], "7");
	
#if defined ELO_RESET_INTEGRATION
	if((ss_elors_get_cvar_skiny() == 1 || ss_elors_get_cvar_skiny() == 3) && ss_elors_get_user_winner(id) == 1)
		menu_additem(menu, item[2], "8");
#endif
	
	menu_setprop(menu, MPROP_NEXTNAME, "\y-->");
	menu_setprop(menu, MPROP_BACKNAME, "\y<--");
	menu_setprop(menu, MPROP_EXITNAME, "\r[X]");
	menu_display(id, menu);
}

public menu_glowne_handle(id, menu, item)
{
	if(item == MENU_EXIT)
	{
		menu_destroy(menu);
		return PLUGIN_CONTINUE;
	}
	
	new dostep, info[6], nazwa[6], cb;
	menu_item_getinfo(menu, item, dostep, info,charsmax(info), nazwa,charsmax(nazwa), cb);
	
	switch(str_to_num(info))
	{
		case 0: menu_glowne(id);
		case 1: rank(id);
		case 2: top15(id);
		case 3: client_cmd(id, "clans_menu");
		case 4: lista_rang(id);
#if defined ELO_RESET_INTEGRATION
		case 5: ss_elors_show_top_players_menu(id);
#endif
		case 6:
		{
			komunikaty[id] = !komunikaty[id];
			menu_glowne(id);
		}
		case 7:
		{
			hud[id] = !hud[id];
			menu_glowne(id);
		}
#if defined ELO_RESET_INTEGRATION
		case 8:
		{
			ss_elors_onoff_models(id);
			menu_glowne(id);
		}
#endif
	}
	
	return PLUGIN_CONTINUE;
}

public rank(id)
{
	new item[10][48], nick[33];
	new menu = menu_create("\w--\y==\r| \wRANK \r|\y==\w--\R", "rank_handle");
	
	get_user_name(id, nick, charsmax(nick));
	
	menu_additem(menu, "\y<-- WROC^n");
	
	formatex(item[0],charsmax(item[]), "\wTwoj nick: \r%s", nick);
	formatex(item[1],charsmax(item[]), "\wPozycja w rankingu: \r%d\d/\y%d", rank_position[id], rank_maxplayers);
	formatex(item[2],charsmax(item[]), "\wPunkty ELO: \r%d", punkty_elo[id]);
	formatex(item[3],charsmax(item[]), "\wRanga: \r%s", nazwa_rangi[ranga[id]]);
	formatex(item[4],charsmax(item[]), "\wZabojstwa: \r%d", ilosc_zabojstw[id]);
	formatex(item[5],charsmax(item[]), "\wZabojstwa w glowe: \r%d", ilosc_zabojstw_hs[id]);
	formatex(item[6],charsmax(item[]), "\wProcent headshot'ow: \r%.1f%", ilosc_zabojstw_hs[id] > 0 ? ((float(ilosc_zabojstw_hs[id]) / float(ilosc_zabojstw[id])) * 100.0) : float(0));
	formatex(item[7],charsmax(item[]), "\wZgony: \r%d", ilosc_zgonow[id]);
	formatex(item[8],charsmax(item[]), "\wAsysty: \r%d", ilosc_asyst[id]);
	formatex(item[9],charsmax(item[]), "\wMVP: \r%d", ilosc_mvp[id]);
	menu_additem(menu, item[0]);
	menu_additem(menu, item[1]);
	menu_additem(menu, item[2]);
	menu_additem(menu, item[3]);
	menu_additem(menu, item[4]);
	menu_additem(menu, item[5]);
	menu_additem(menu, item[6]);
	menu_additem(menu, item[7]);
	menu_additem(menu, item[8]);
	menu_additem(menu, item[9]);

	menu_setprop(menu, MPROP_NEXTNAME, "\y-->");
	menu_setprop(menu, MPROP_BACKNAME, "\y<--");
	menu_setprop(menu, MPROP_EXITNAME, "\r[X]");
	menu_display(id, menu);
}

public rank_handle(id, menu, item)
{
	if(item == MENU_EXIT)
	{
		menu_destroy(menu);
		return PLUGIN_CONTINUE;
	}
	
	switch(item)
	{
		case 0: menu_glowne(id);
		default: rank(id);
	}
	
	return PLUGIN_CONTINUE;
}

public top15(id)
{
	new top15[2048];
	new maxplayers = rank_maxplayers;
	new len = 0;

	if(maxplayers > 15)
		maxplayers = 15;

	len = formatex(top15, charsmax(top15), "<meta charset=utf-8><body bgcolor=#000000 style=^"font-family: sans-serif;^"><font color=#ffffff><pre>");
	len += formatex(top15[len], charsmax(top15) - len, "%2s %-22.22s %15s %15s %10s %10s %10s^n", "#", "Nick", "Punkty ELO", "Zabójstwa", "Zgony", "MVP", "Procent HS");
	
	mysql_baza = SQL_MakeDbTuple(hostname, username, password, database);

	new Handle: connection = SQL_Connect(mysql_baza, err, error,charsmax(error));
	new Handle: query;
		
	query = SQL_PrepareQuery(connection, "SELECT `nick`, `elo_points`, `kills`, `kills_hs`, `deaths`, `mvp` FROM `elo_rank_system` ORDER BY `elo_points` DESC LIMIT 15;");
	SQL_Execute(query);

	if(SQL_NumResults(query) > 0)
	{
		new top_nick[33], top_elo, top_kills, top_kills_hs, top_deaths, top_mvp;
		
		for(new i = 1; i <= maxplayers && charsmax(top15) - len > 0; i++)
		{
			SQL_ReadResult(query, 0, top_nick, charsmax(top_nick));
			top_elo     = SQL_ReadResult(query, 1);
			top_kills    = SQL_ReadResult(query, 2);
			top_kills_hs = SQL_ReadResult(query, 3);
			top_deaths  = SQL_ReadResult(query, 4);
			top_mvp  = SQL_ReadResult(query, 5);
		
			replace_all(top_nick, charsmax(top_nick), "<", "[");
			replace_all(top_nick, charsmax(top_nick), ">", "]");
			
			len += formatex(top15[len], charsmax(top15) - len, "%2d %-22.22s %8d %14d %11d %8d %13.1f%%^n", 
			i, top_nick, top_elo, top_kills, top_deaths, top_mvp, top_kills_hs > 0 ? ((float(top_kills_hs) / float(top_kills)) * 100) : float(0));
			
			SQL_NextRow(query);
		}
	}
		
	show_motd(id, top15, "TOP 15");
		
	SQL_FreeHandle(query);
	SQL_FreeHandle(connection);
}

public lista_rang(id)
{
	new item[48];
	new menu = menu_create("\w--\y==\r| \wLISTA RANG \r|\y==\w--\R", "lista_rang_handle");
	
	menu_additem(menu, "\y<-- WROC^n");
	
	for(new i = 1; i <= ilosc_rang; i++)
	{
		formatex(item,charsmax(item), "\w%s \r[OD \y%d \rPKT]", nazwa_rangi[i], prog_rangi[i]);
		menu_additem(menu, item);
	}
	
	menu_setprop(menu, MPROP_NEXTNAME, "\y-->");
	menu_setprop(menu, MPROP_BACKNAME, "\y<--");
	menu_setprop(menu, MPROP_EXITNAME, "\r[X]");
	menu_display(id, menu);
}

public lista_rang_handle(id, menu, item)
{
	if(item == MENU_EXIT)
	{
		menu_destroy(menu);
		return PLUGIN_CONTINUE;
	}
	
	switch(item)
	{
		case 0: menu_glowne(id);
		default: lista_rang(id);
	}
	
	return PLUGIN_CONTINUE;
}

//=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-//
// ZAPIS I ODCZYT DANYCH
//=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-//
public wczytaj_dane(id)
{	
	mysql_baza = SQL_MakeDbTuple(hostname, username, password, database);

	new Handle: connection = SQL_Connect(mysql_baza, err, error,charsmax(error));
	new Handle: query;
	
	new nick[33];
	get_user_name(id, nick,charsmax(nick));
	
	query = SQL_PrepareQuery(connection, "SELECT count(*) AS `pozycja` FROM `elo_rank_system` WHERE elo_points > (SELECT `elo_points` FROM `elo_rank_system` WHERE `nick` = '%s');", nick);
	SQL_Execute(query);
	
	if(SQL_NumResults(query) > 0)
		rank_position[id] = (SQL_ReadResult(query, 0) + 1);
	else
		zapisz_dane(id);
	
	query = SQL_PrepareQuery(connection, "SELECT * FROM `elo_rank_system` WHERE `nick` = '%s';", nick);
	SQL_Execute(query);
	
	if(SQL_NumResults(query) > 0)
	{
		punkty_elo[id]        = SQL_ReadResult(query, 2);
		ilosc_zabojstw[id]    = SQL_ReadResult(query, 3);
		ilosc_zabojstw_hs[id] = SQL_ReadResult(query, 4);
		ilosc_zgonow[id]      = SQL_ReadResult(query, 5);
		ilosc_asyst[id]       = SQL_ReadResult(query, 6);
		ilosc_mvp[id]         = SQL_ReadResult(query, 7);
		komunikaty[id]        = SQL_ReadResult(query, 8);
		hud[id]               = SQL_ReadResult(query, 9);
	}
	else zapisz_dane(id);
	
	query = SQL_PrepareQuery(connection, "SELECT `id` FROM `elo_rank_system`;");
	SQL_Execute(query);
	
	rank_maxplayers = SQL_NumResults(query);
	
	SQL_FreeHandle(query);
	SQL_FreeHandle(connection);
	
	set_task(1.0, "nadaj_range", (id + TASK_RANGA),_,_,"b");
}

public zapisz_dane(id)
{
	mysql_baza = SQL_MakeDbTuple(hostname, username, password, database);

	new Handle: connection = SQL_Connect(mysql_baza, err, error,charsmax(error));
	new Handle: query;
	
	new nick[33];
	get_user_name(id, nick,charsmax(nick));

	query = SQL_PrepareQuery(connection, "SELECT `id` FROM `elo_rank_system` WHERE `nick` = '%s';", nick);
	SQL_Execute(query);
	
	if(SQL_NumResults(query) > 0)
	{	
		query = SQL_PrepareQuery(connection, "UPDATE `elo_rank_system` SET `elo_points` = '%d', `kills` = '%d', `kills_hs` = '%d', `deaths` = '%d', `assists` = '%d', `mvp` = '%d', `komunikaty` = '%d', `hud` = '%d' WHERE `nick` = '%s';", 
		punkty_elo[id], ilosc_zabojstw[id], ilosc_zabojstw_hs[id], ilosc_zgonow[id], ilosc_asyst[id], ilosc_mvp[id], komunikaty[id], hud[id], nick);
		SQL_Execute(query); 
	}
	else
	{
		query = SQL_PrepareQuery(connection, "INSERT INTO `elo_rank_system` VALUES (NULL, '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d');", 
		nick, punkty_elo[id], ilosc_zabojstw[id], ilosc_zabojstw_hs[id], ilosc_zgonow[id], ilosc_asyst[id], ilosc_mvp[id], komunikaty[id], hud[id]);
		SQL_Execute(query); 
	}
	
	SQL_FreeHandle(query);
	SQL_FreeHandle(connection);
}

public aktualizuj_rank(id)
{
	mysql_baza = SQL_MakeDbTuple(hostname, username, password, database);

	new Handle: connection = SQL_Connect(mysql_baza, err, error,charsmax(error));
	new Handle: query;
	
	new nick[33];
	get_user_name(id, nick,charsmax(nick));
	
	query = SQL_PrepareQuery(connection, "SELECT `id` FROM `elo_rank_system` WHERE `nick` = '%s';", nick);
	SQL_Execute(query);
	
	if(SQL_NumResults(query) > 0)
	{	
		query = SQL_PrepareQuery(connection, "UPDATE `elo_rank_system` SET `elo_points` = '%d', `kills` = '%d', `kills_hs` = '%d', `deaths` = '%d', `assists` = '%d', `mvp` = '%d', `komunikaty` = '%d', `hud` = '%d' WHERE `nick` = '%s';", 
		punkty_elo[id], ilosc_zabojstw[id], ilosc_zabojstw_hs[id], ilosc_zgonow[id], ilosc_asyst[id], ilosc_mvp[id], komunikaty[id], hud[id], nick);
		SQL_Execute(query); 
	}
	else
	{
		query = SQL_PrepareQuery(connection, "INSERT INTO `elo_rank_system` VALUES (NULL, '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d');", 
		nick, punkty_elo[id], ilosc_zabojstw[id], ilosc_zabojstw_hs[id], ilosc_zgonow[id], ilosc_asyst[id], ilosc_mvp[id], komunikaty[id], hud[id]);
		SQL_Execute(query); 
	}
	
	query = SQL_PrepareQuery(connection, "SELECT `id` FROM `elo_rank_system`;");
	SQL_Execute(query);
	
	rank_maxplayers = SQL_NumResults(query);
	
	query = SQL_PrepareQuery(connection, "SELECT count(*) AS `pozycja` FROM `elo_rank_system` WHERE elo_points > (SELECT `elo_points` FROM `elo_rank_system` WHERE `nick` = '%s');", nick);
	SQL_Execute(query);
	
	if(SQL_NumResults(query) > 0)
		rank_position[id] = (SQL_ReadResult(query, 0) + 1);
		
	SQL_FreeHandle(query);
	SQL_FreeHandle(connection);
}

//=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-//
// STOCKI
//=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-//
stock odmiana(ile, czlon[], zero[], jeden[], dwa[])
{
	new odmienione_slowo[33];
	
	ile = abs(ile);
	
	if(ile == 1)
	{
		formatex(odmienione_slowo,charsmax(odmienione_slowo), "%s%s", czlon, jeden);
		return odmienione_slowo;
	}
	if((ile % 10 == 2 || ile % 10 == 3 || ile % 10 == 4) && (!(ile % 100 == 12 || ile % 100 == 13 || ile % 100 == 14)))
	{
		formatex(odmienione_slowo,charsmax(odmienione_slowo), "%s%s", czlon, dwa);
		return odmienione_slowo;
	}
	
	formatex(odmienione_slowo,charsmax(odmienione_slowo), "%s%s", czlon, zero);
	return odmienione_slowo;
}