import mysql.connector, sys, gensim, logging, re, codecs, os

if(len(sys.argv) > 1):
  # paduodi reiksmes perskirtas kableliu, is ju padaro masyva
  arr = sys.argv[1].split(',')

mydb = mysql.connector.connect(
  host="localhost",
  user="root",
  password="",
  database='trenderis'
)
cursor = mydb.cursor()
if(len(sys.argv) > 1):
  query = ("SELECT id, original_text FROM texts "
         "WHERE trained_word2vec='0' AND id IN (" + sys.argv[1] + ")")
else:
  query = ("SELECT id, original_text FROM texts "
         "WHERE trained_word2vec='0' LIMIT 1000")
cursor.execute(query)
myresult = cursor.fetchall()

if(os.path.exists("./word2vec")):
  model = gensim.models.Word2Vec.load("./word2vec")
for text in myresult:
  t = text[1].lower();
  sentences = re.split(r' *[\.\?!][\'"\)\]]* *', str(t))
  sentences_split = []
  for s in sentences:
      sentences_split.append(s.split())
  if(os.path.exists("./word2vec")):
    model.build_vocab(sentences_split, update=True)
    model.train(sentences_split, total_examples=len(sentences_split), epochs=model.epochs)
    model.save('./word2vec')
  else:
    model = gensim.models.Word2Vec(sentences_split, window=3, min_count=1)
    model.save('./word2vec')

  sql = "UPDATE texts SET trained_word2vec = '1' WHERE id=" + str(text[0])
  cursor.execute(sql)
  mydb.commit()

  print(text[0])

cursor.close()
mydb.close()