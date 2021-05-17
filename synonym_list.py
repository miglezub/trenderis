import mysql.connector, sys, gensim, logging, re, codecs, json

if(len(sys.argv) > 1):
  model = gensim.models.Word2Vec.load("./word2vec")

  mydb = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database='trenderis'
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
          word['syn'] = json.dumps(model.wv.most_similar(word['w'], topn=3))
          j += 1
        else: break
      query = ("UPDATE text_analysis set results='" + json.dumps(json_result, ensure_ascii=False) + "' WHERE id=" + str(analysis[0]))
      cursor.execute(query)
      mydb.commit()
  cursor.close()
  mydb.close()
else:
  print("Texts not specified")