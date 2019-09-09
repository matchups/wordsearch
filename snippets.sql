// supervocalics
select text, length from words where bank like "a%e%i%o%u%" and text not like "%a%a%" and text not like "%e%e%" and text not like "%i%i%" and text not like "%o%o%" and text not like "%u%u%" and length = length (bank) and length > 15

select text, length (text), length (bank) from words where text like "%u%u%" and text like "%o%o%" and text not like "%e%e%e%"  and text not like "%a%a%a%"  and text like "%a%a%"  and text like "%i%i%"  and text like "%e%e%"  and text not like "%i%i%i%"  and text not like "%o%o%o%"  and text not like "%u%u%u%" 

select text, length (text), length (bank) from words where text like "%u%u%u%" and text like "%o%o%o%" and text not like "%e%e%e%e%"  and text not like "%a%a%a%a%"  and text like "%a%a%a%"  and text like "%i%i%i%"  and text like "%e%e%e%"  and text not like "%i%i%i%i%"  and text not like "%o%o%o%o%"  and text not like "%u%u%u%u%" 

select * from words where length > 20 and bank rlike "^[^eiou]*$"

// Best letter banks
http://8wheels.org/wordsearch/dumper.php?sql=select+bank%2C+count%28id%29+from+words+group+by+bank+having+count%28id%29+%3E+150

// Recent sessions
select started, username from session inner join user on user.id = session.user_id where started > "2019-07-01"

SELECT distinct X.text FROM entry A, entry B, words X INNER JOIN word_entry Y ON Y.word_id = X.id INNER JOIN entry_cat C ON C.entry_id = Y.entry_id INNER JOIN category T on T.id = C.cat_id WHERE A.corpus_id = 89 AND B.corpus_id = 89 AND X.text = concat(A.name, B.name) AND T.title LIKE "%people%"
// need to filter by category

SHOW index FROM xxx

SELECT corpus_id, count (1) FROM entry GROUP BY corpus_id