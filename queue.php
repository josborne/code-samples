/*
 * Queue implementation using two stacks
 */

class Queue
{
    private $inStack = array();
    private $outStack = array();

    public function enqueue($item)
    {
        array_push($this->inStack, $item);
    }

    public function dequeue()
    {
        if (count($this->outStack) > 0)
            return array_pop($this->outStack);

        if (count($this->inStack) === 0)
        {
            return 0; //ERROR
        }

        //push instack onto outstack, therefore putting oldest element at top (to act like a queue)
        while (count($this->inStack) > 0)
        {
            array_push($this->outStack, array_pop($this->inStack));
        }

        return array_pop($this->outStack);
    }
}