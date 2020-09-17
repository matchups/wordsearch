import sys
import array
# sys.path.append('/home/stufffromh/public_html/dmitri')
sys.path.append ('c:/users/xcorp/appdata/local/packages/pythonsoftwarefoundation.python.3.8_qbz5n2kfra8p0/localcache/local-packages/python38/site-packages')
import mysql.connector
import os
import urllib3
import json
import math
import re
import gspread
from oauth2client.service_account import ServiceAccountCredentials

# Please see lines 220 and 249 for some magic numbers and formulas which are not pulled out
# because they don't have good names or relate to real-word entities.

def csv2array (key_column, csv):
	mode = "T" # titles
	data = []
	index = {}
	for row in csv:
		if mode == "T":
			headings = row
			mode = "A" # abbreviations
		elif mode == "A":
			abbrs = row
			abbr_heading = {}
			for colnum in range (len (row)):
				abbr_heading [abbrs[colnum]] = headings [colnum]
			mode = "D" # data
		else:
			row_data = {}
			for colnum in range (len (row)):
				row_data [abbrs[colnum]] = row [colnum]
			data.append (row_data)
			index [row_data [key_column]] = len (data) - 1
	return abbr_heading, index, data

def get_rules (rule):
    sheet_dump = 'foo'

def parse_command ():
    # parse command line
    # -p percentage
    # -r rule_abbreviation
    # -ra all rules
    # -rfps -rtps -rcap -rpf -rpl -rrx
    # -s source database ID
    skip = False
    percent = {'n': 100, 'd': 100}
    rcount = 0
    source = '?'
    rule_details = {}
    global debug_flag
    debug_flag = False
    args = sys.argv
    args.append ('') # so we can always get 'next'
    for argpos in range (1, len (args) - 1):
       arg = args[argpos]
       next = args[argpos + 1]
       if skip:
          skip = False
       elif arg == '-p':
          na = next.split('/')
          percent['n'] = int (na[0])
          if len(na) > 1:
              percent['d'] = int (na[1])
          if percent['n'] < 1  or  percent['d'] < 1  or  percent['n'] > percent['d']:
              raise Exception ('Invalid percentage')
          skip = True
       elif arg == '-r':
          skip = True
          rcount += 1
          rule_details = get_rules (next)
       elif arg == '-ra':
          rcount += 1
          rule_details = get_rules ('*all')
          raise Exception ('-ra not supported yet')
       elif arg == '-debug':
          debug_flag = True
       elif arg[0:2] == '-r':
         rule_details [arg[2:]] = next.replace('#', '<')
         skip = True
       elif arg == '-s':
         source = next
         skip = True
       elif arg == '-h' or arg == '-?' or arg == '-help' or arg == '/?':
         raise Exception ('expansion -s sourcedb {-r ruleid | -ra | -rfps from_pos -rtps to_pos -rxf transform [-rcap caps_factor] [-rpwd phrase_word] [-rpwt phrase_weight] [-rrx regular_expression]} [-p percent]')
       else:
         raise Exception ('Invalid parameter: ' + arg)

    if len(rule_details) > 0:
        rcount += 1
        if not ('fps' in rule_details) or not ('tps' in rule_details):
            raise Exception ('From and To parts of speech are required')
        if not ('xf' in rule_details):
            raise Exception ('Transform is required')
        if not ('cap' in rule_details): #capitalization factor
            rule_details ['cap'] = .5
        if not ('pwd' in rule_details): #how to deal with phrases
            rule_details ['pwd'] = 'N'
        if not ('pwt' in rule_details): #factor
            rule_details ['pwt'] = 1.0
        if not ('cap' in rule_details): #capitalized words factor
            rule_details ['cap'] = 1.0
        if not ('rx' in rule_details): #regular expression filter
            rule_details ['rx'] = ''
        if len(rule_details) > 7:
            raise Exception ("Invalid rule detail")
        rule_details ['name'] = 'ad hoc rule'

    if rcount == 0:
        raise Exception ('No rule specified')

    if rcount > 1:
        raise Exception ('Conflicting rule specifications')

    if source == '?':
        raise Exception ('No source database specified')

    # Handle rule IDs
    return percent, source, rule_details

def open_database():
    ODBC_FILE = os.path.join(os.path.expanduser('~'),os.path.join('.odbc','BWP.odbc'))
    dsn_dict = dict()
    with open(ODBC_FILE,'r') as fid:
        reading_info = False
        for line in fid.readlines():
            if '=' in line and not line.startswith('#'):
                prop,val = [_.strip() for _ in line.split('=')]
                dsn_dict[prop] = val

    return mysql.connector.connect(
      host="localhost",
      user=dsn_dict['USER'],
      passwd=dsn_dict['PASS'],
      database=dsn_dict['DB']
    )

def wiki_count (word):
    url = 'https://en.wikipedia.org/w/api.php?action=query&list=search&srinfo=totalhits&srlimit=1&format=json&srsearch=' + (word.replace (' ', '_'))
    page = http.request('GET', url)
    return json.loads (page.data)['query']['searchinfo']['totalhits']

def gooch_score (test_word, fid):
    test_word = test_word.upper().replace(' ', '').replace('-', '')
    while True:
        line = fid.readline().strip().split()
        gooch_word = line[0]
        if gooch_word == test_word:
            return len(line) - 1
        if gooch_word > test_word:
            return 0

def sig_digits (value, digits):
    return re.match ('[0.]*.{' + str(digits) + '}', str (value)).group()

def debug (arg):
    if (debug_flag):
        sys.stderr.write(str(arg) + '\n')

# Main
try:
    percent, source, rule_details = parse_command ()
except Exception as e:
    sys.exit ('Command line error: ' + e.args[0])

debug (percent)
debug (source)
debug (rule_details)

base_query = 'FROM entries INNER JOIN definitions ON definitions.entry_id = entries.entry_id {1}'
base_query += ' WHERE definitions.source_id = ' + str (source)
base_query += ' AND definitions.pos = "' + rule_details['fps'] + '"'
if rule_details['rx']:
    base_query += ' AND entries.entry RLIKE \'' + rule_details['rx'] + '\''
if rule_details ['pwd'] == 'N':
    base_query += ' AND entries.entry NOT RLIKE \' \''

count_query = ('SELECT count(distinct entries.entry_id) ' + base_query).replace("{1}", '')
debug (count_query)

list_query = ('SELECT distinct entries.entry, entries.entry_id ' + base_query).replace("{1}", \
    'INNER JOIN canonical ON canonical.canonical_id = entries.canonical_id')
if percent['n'] < percent['d']:
    list_query += ' AND (entries.entry_id + entries.canonical_id / 23) % ' + str (percent['d']) + ' < ' + str (percent['n'])
debug (list_query)

xf = rule_details['xf']
if xf[-1] == '+':
    xf_entry = '\'' + xf[:-1] + '\' + entry'
    xf_pattern = '\'^' + xf[:-1] + '\''
elif xf[0] == '+':
    parse = re.match('\+(<*)(=?)(.*)', xf)
    chop = len(parse.group(1))
    repeat = len(parse.group(2))
    suffix = parse.group(3)
    xf_pattern = '\'' + suffix + '$\''
    if chop > 0:
        xf_entry = 'substring(entry, ' + str(chop+1) + ', 99) + \'' + suffix + '\''
    else:
        xf_entry = 'entry + \'' + suffix + '\''
else:
    raise Exception ('Invalid transform')
xf_count_query = count_query + ' AND entry RLIKE ' + xf_pattern
debug (xf_count_query)

mydb = open_database()
cur = mydb.cursor()
cur.execute(count_query,())
prior_count = cur.fetchall()[0][0]
sys.stderr.write(str(prior_count) + " entries found.\n")

cur.execute(xf_count_query,())
xf_count = cur.fetchall()[0][0]
sys.stderr.write(str(xf_count) + " transformed entries found.\n")

fraction = (xf_count + 0.0) / prior_count
weight = math.sqrt (fraction)
sys.stderr.write("That's " + sig_digits (fraction * 100.0 + .005, 3) + '%, giving a weight of ' + sig_digits (weight, 3) + '\n')

single_query = 'SELECT count(entries.entry_id) ' \
    ' FROM entries INNER JOIN definitions ON definitions.entry_id = entries.entry_id' \
    ' WHERE definitions.source_id = ' + str (source) + \
    ' AND definitions.pos = "' + rule_details['tps'] + '"' \
    ' AND entries.entry = '
mydb2 = open_database()
cursq = mydb2.cursor()

list_query += " ORDER BY canonical.canonical_form"
cur.execute(list_query,())
counter = 0
urllib3.disable_warnings()
http = urllib3.PoolManager()

GOOCH_FILE = '../gooch.txt'
gooch_fid = open(GOOCH_FILE,'r')
goochxf_fid = open(GOOCH_FILE,'r')

while True:
    row = cur.fetchone()
    if row == None:
        break
    base_word = row[0]
    base_count = wiki_count (base_word)
    base_gooch = gooch_score (base_word, gooch_fid)
    base_score = ((.0275 * math.log (base_count) + .1) if base_count else 0) + (1.0 - .7**base_gooch) / 2.0

    words = base_word.split(' ')
    if len (words) > 1:
        here = 0 if rule_details['pwd'] == 'F' else -1
        change_word = words[here]
        bonus_factor = float (rule_details['pwt'])
    else:
        change_word = base_word
        bonus_factor = 1

    if re.search ('[A-Z]', base_word):
        bonus_factor *= float (rule_details['cap'])

    if xf[-1] == '+':
        xf_word = xf[:-1] + change_word
    elif xf[0] == '+':
        xf_word = (change_word[:-chop] if chop else change_word) + (change_word[-1] if repeat else '') + suffix

    if len (words) > 1:
        words[here] = xf_word
        xf_word = ' '.join(words)

    cursq.execute(single_query + '"' + xf_word + '"',())
    if cursq.fetchall()[0][0]:
        continue

    xf_count = wiki_count (xf_word)
    xf_gooch = gooch_score (xf_word, goochxf_fid)
    xf_score = ((.01 * math.log (xf_count) + .35) if xf_count else 0) + (1.0 - .4**(xf_gooch + .2)) / 2.0 * bonus_factor

    score = weight * base_score * xf_score
    if debug_flag:
        print (base_word, base_count, base_gooch, sig_digits (base_score, 3), xf_word, xf_count, xf_gooch, sig_digits (xf_score, 3), sig_digits (score, 3))
    out_dict =  {
      "word": xf_word,
      "source": rule_details['name'] + ' on ' + base_word,
      "pos": rule_details['tps'],
      "score": sig_digits (score, 3)
      }
    print (',' if counter else '{') + str (counter) + ':' + json.dumps (out_dict)
    counter += 1
# Need to deal with successor rules
print ('}')
