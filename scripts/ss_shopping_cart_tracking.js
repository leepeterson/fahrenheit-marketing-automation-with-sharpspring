var _ss = _ss || [];

(function(){
  var data              = ss_shopping_cart_tracking_data;
  var transaction_data  = ss_shopping_cart_tracking_data.transaction_data;

  var debug = 0;
  if (debug) console.log(data);

  function addItems(){
    // Set recently removed items to quantity 0
    var removed = data.removed_cart_contents;
    for (var key in removed){
      if (removed.hasOwnProperty(key)){
        var item = removed[key];
        item.quantity = 0;
        if (debug) console.log(item);
        _ss.push(['_addTransactionItem', item ]);
      }
    }

    // Add cart contents to transaction
    // Items that have been removed and re-added will appear in both lists!
    var added = data.cart_contents;
    for (var key in added){
      if (added.hasOwnProperty(key)){
        var item = added[key];
        if (debug) console.log(item);
        _ss.push(['_addTransactionItem', item ]);
      }
    }

    if (data.orderComplete){
      _ss.push(['_completeTransaction', { 'transactionID': data.transaction_data.transactionID } ]);
    }
  }

  _ss.push(['_setTransaction', transaction_data, addItems ]);
})();

