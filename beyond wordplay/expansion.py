import sys
import array

# parse command line
# -p percentage
# -r rule_abbreviation
# -ra all rules
# -rfps -rtps -rcap -rpf -rpl -rrx
# -s source database ID
skip = False
percent = 100
rules = '*err'
rcount = 0
source = '?'
rule_details = {}
args = sys.argv
args.append ('') # so we can always get 'next'
for argpos in range (1, len (args) - 1):
   arg = args[argpos]
   next = args[argpos + 1]
   if skip:
      skip = False
   elif arg == '-p':
      percent = int (next)
      if percent < 1 or percent > 100:
          sys.exit ('Invalid percentage')
      skip = True
   elif arg == '-r':
      rules = next
      skip = True
      rcount += 1
   elif arg == '-ra':
      rules = '*all'
      skip = True
      rcount += 1
   elif arg[0:2] == '-r':
     rule_details [arg[2:]] = next
     skip = True
   elif arg == '-s':
     source = next
     skip = True
   else:
       print '((' + arg[0:2] + '))'
       sys.exit ('Invalid parameter: ' + arg)

if len(rule_details) > 0:
    rcount += 1
    if not ('fps' in rule_details) or not ('tps' in rule_details):
        sys.exit ('From and To parts of speech are required')
    if not ('cap' in rule_details):
        rule_details ['cap'] = .5
    if not ('pf' in rule_details):
        rule_details ['pf'] = 0
    if not ('pl' in rule_details):
        rule_details ['pl'] = .0
    if not ('rx' in rule_details):
        rule_details ['rx'] = ''
    if len(rule_details) > 6:
        sys.exit ("Invalid rule detail")

if rcount == 0:
    sys.exit ('No rule specified')

if rcount > 1:
    sys.exit ('Conflicting rule specifications')

if source == '?':
    sys.exit ('No source database specified')

# Loop through rules
if len(rule_details) > 0:
    print rule_details
else:
    print rules
