import sys, gensim, logging, re, codecs

if(len(sys.argv) > 1):
  model = gensim.models.Word2Vec.load("./word2vec")
  if str(sys.argv[1]) in model.wv.vocab:
    print(model.wv.most_similar(str(sys.argv[1]), topn=3))
  else:
    print("Word not in vocabulary")
else:
  print("Word not specified")