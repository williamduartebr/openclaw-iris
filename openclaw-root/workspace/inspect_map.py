import json
p='/root/.openclaw/workspace/skills_ui.js.map'
obj=json.load(open(p))
for s,c in zip(obj.get('sources',[]), obj.get('sourcesContent',[])):
    txt=c or ''
    if 'upload' in txt.lower() or 'fetch(' in txt or '/api' in txt or '/skills' in txt:
        print('---SOURCE',s,'---')
        for kw in ['upload','fetch(','/api','/skills','importSkill']:
            idx=txt.find(kw)
            if idx!=-1:
                print(txt[max(0,idx-400):idx+1200])
                print('\n')
