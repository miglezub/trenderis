import mysql.connector, sys, gensim, logging, re, codecs, json

if(len(sys.argv) > 1):
  model = gensim.models.Word2Vec.load("../word2vec")

  mydb = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database='trenderis',
    charset='utf8',
  )
  cursor = mydb.cursor()

  ids = sys.argv[1].split(',')
  for i in ids:
    query = ("SELECT id, results FROM text_analysis "
         "WHERE text_id='" + i + "' ORDER BY id DESC LIMIT 1")
    cursor.execute(query)
    analysis = cursor.fetchone()
    if analysis is not None:
      print(analysis[0])
      json_result = json.loads(analysis[1])
      j = 0
      for word in json_result:
        if j < 20:
          # word['w'] = str(word['w']).encode('utf8')
          if str(word['w']) in model.wv.vocab:
            word['syn'] = json.dumps(str(model.wv.most_similar(str(word['w']), topn=3)))
            print(word['syn'])
          j += 1
        else: break
      print(json.dumps(json_result, ensure_ascii=False))
      sql = "UPDATE text_analysis set results=%s WHERE id=%s"
      cursor.execute(sql, (json.dumps(json_result, ensure_ascii=False), str(analysis[0])))
      mydb.commit()
  cursor.close()
  mydb.close()
else:
  print("Texts not specified")